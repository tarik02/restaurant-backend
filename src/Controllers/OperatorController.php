<?php
namespace App\Controllers;

use App\Services\Uploads;
use App\Util\Format;
use App\Util\OrderStatus;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

class OperatorController extends Controller {
  /** @var Container */
  protected $container;

  /** @var Uploads */
  protected $uploads;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->container = $container;
    $this->uploads = $this->container['uploads'];
  }

  public function orders(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'operator');

    $page = $request->getParsedBodyParam('page', 1);
    $perPage = $this->bound(5, 50, $request->getParsedBodyParam('perPage', 15));
    $filter = $request->getParsedBodyParam('filter', 1);

    $query = DB::table('orders')
      ->orderBy('created_at', 'desc');
    if ($statusFilter = $filter['status'] ?? null) {
      $query->whereIn('status', array_map(function (string $name) {
        return OrderStatus::fromString($name);
      },$filter['status']));
    }
    $query->forPage($page, $perPage);

    $total = $query->getCountForPagination();
    $orders = $query->get();

    return $response->withJson([
      'data' => $orders->map(function(array $order) {
        return [
          'id' => $order['id'],
          'name' => $order['contact_name'],
          'phone' => $order['phone'],

          'created_at' => Format::dateTime($order['created_at']),
          'price' => $order['price'],
          'status' => OrderStatus::toString($order['status']),

          'address' => $order['address'],
          'lat' => $order['latitude'],
          'lng' => $order['longtitude'],

          'driver' => $order['driver_id'],
        ];
      }),

      'total' => $total,
    ]);
  }

  public function courses(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'operator');

    $courses = DB::table('courses')->orderBy('id', 'desc')->get();
    $coursesIds = $courses->pluck('id');

    $ingredients = DB::table('courses_ingredients')
      ->whereIn('course_id', $coursesIds)
      ->get()
      ->groupBy('course_id');

    $images = DB::table('courses_images')
      ->whereIn('course_id', $coursesIds)
      ->get()
      ->groupBy('course_id');

    return $response->withJson([
      'data' => $courses->map(function(array $course) use($ingredients, $images, $request) {
        $id = intval($course['id']);

        /** @var Collection|null $courseIngredients */
        $courseIngredients = $ingredients[$id] ?? null;

        /** @var Collection|null $courseImages */
        $courseImages = $images[$id] ?? null;

        return [
          'id' => $id,
          'title' => $course['title'],
          'description' => $course['description'],
          'images' => $courseImages !== null
            ? collect($courseImages)
              ->pluck('src')->toArray()
            : [],
          'price' => intval($course['price']),
          'visible' => boolval($course['visible']),

          'ingredients' => $courseIngredients !== null
            ? $courseIngredients
              ->pluck('amount', 'ingredient_id')
              ->map(function($v) {
                return floatval($v);
              })
            : [],
        ];
      }),
    ]);
  }

  public function courseSave(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'operator');

    $body = json_decode($request->getParsedBodyParam('data'), true);
    $files = $request->getUploadedFiles()['files'] ?? [];

    $id = $body['id'] ?? null;
    $images = $body['images'];

    $this->container->get('logger')->info('HERE');
    $this->container->get('logger')->info($request->getParsedBodyParam('data'));

    $firstNull = 0;
    /** @var UploadedFile $file */
    foreach ($files as $file) {
      $public = $this->uploads->upload($file);

      while ($images[$firstNull] ?? null !== null) {
        ++$firstNull;
      }
      $images[$firstNull++] = $public;
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    return DB::connection()->transaction(function() use($id, $body, $images, $response) {
      $courses = DB::table('courses');
      $coursesIngredients = DB::table('courses_ingredients');
      $coursesImages = DB::table('courses_images');

      if ($id !== null) {
        $coursesIngredients->where('course_id', $id)->delete();
        $coursesImages->where('course_id', $id)->delete();
      }

      $data = [
        'title' => $body['title'],
        'description' => $body['description'],
        'price' => $body['price'],
        'visible' => $body['visible'],
      ];

      if ($id === null) {
        $id = $courses->insertGetId($data);
      } else {
        $courses->where('id', $id)->update($data);
      }

      $coursesIngredients->insert(
        collect($body['ingredients'])
          ->mapWithKeys(function (float $amount, int $ingredient) use ($id) {
            return [
              $ingredient => [
                'course_id' => $id,
                'ingredient_id' => $ingredient,
                'amount' => $amount,
              ]
            ];
          })
          ->values()
          ->toArray()
      );

      $coursesImages->insert(
        collect($images)->map(function (string $image) use ($id) {
          return [
            'course_id' => $id,
            'src' => $image,
          ];
        })->toArray()
      );

      return $response->withJson([
        'status' => 'ok',
        'id' => $id,
      ]);
    });
  }

  public function courseRemove(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'operator');

    $id = $request->getParsedBodyParam('id');

    DB::table('courses')->where('id', $id)->delete();
    DB::table('courses_ingredients')->where('course_id', $id)->delete();
    DB::table('courses_images')->where('course_id', $id)->delete();

    return $response->withJson([
      'status' => 'ok',
    ]);
  }

  public function ingredients(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'operator');

    $query = DB::table('ingredients');

    $all = $request->getParam('all', 'false') === 'true';

    if (!$all) {
      $sortBy = $request->getParam('sortBy', 'title');
      $descending = $request->getParam('descending', 'false') === 'true';

      if (!in_array($sortBy, ['title', 'price', 'unit'])) {
        return $response->withStatus(500);
      }

      $page = intval($request->getParam('page', 1));
      $perPage = $this->bound(5, 100, intval($request->getParam('perPage', 15)));

      $query
        ->orderBy($sortBy, $descending ? 'desc' : 'asc')
        ->forPage($page, $perPage);
      $total = $query->getCountForPagination();
    } else {
      $page = null;
      $perPage = null;
      $total = null;
    }

    $ingredients = $query->get();

    return $response->withJson([
      'data' => $ingredients->map(function (array $ingredient) {
        return [
          'id' => intval($ingredient['id']),
          'title' => $ingredient['title'],
          'price' => intval($ingredient['price']),
          'unit' => $ingredient['unit'],
          'floating' => boolval($ingredient['floating']),
        ];
      }),

      'pagination' => [
        'page' => $page,
        'perPage' => $perPage,
        'totalCount' => $total,
      ],
    ]);
  }

  public function ingredientSave(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'operator');

    $ingredients = DB::table('ingredients');

    $body = $request->getParsedBody();
    $id = $body['id'] ?? null;

    $data = [
      'title' => $body['title'],
      'price' => $body['price'],
      'unit' => $body['unit'],
      'floating' => $body['floating'],
    ];
    if ($id === null) {
      $id = $ingredients->insertGetId($data);
    } else {
      $ingredients->where('id', $id)->update($data);
    }

    return $response->withJson([
      'status' => 'ok',
      'id' => $id,
    ]);
  }

  public function ingredientDelete(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'operator');

    $id = intval($request->getParam('id'));
    if ($id === 0) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'bad_request',
      ]);
    }

    $ingredients = DB::table('ingredients');
    $coursesIngredients = DB::table('courses_ingredients');

    if ($ingredients->delete($id) !== 1) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'not_exist',
      ]);
    }

    $coursesIngredients->where('ingredient_id', $id)->delete();

    return $response->withJson([
      'status' => 'ok',
    ]);
  }


  protected function bound(int $min, int $max, int $value) {
    return min($max, max($min, $value));
  }
}
