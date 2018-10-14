<?php

namespace App\Controllers;

use App\Services\ReviewsService;
use App\Services\StorageService;
use App\Util\OrderStatus;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class OrderController extends Controller {
  /** @var Connection */
  private $db;

  /** @var ReviewsService */
  private $reviews;

  /** @var StorageService */
  private $storage;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->db = $container['db']->connection();
    $this->reviews = $container['reviews'];
    $this->storage = $container['storage'];
  }

  protected function getOrder(Response $response, int $id, ?string $token = null): array {
    $orders = DB::table('orders');

    $order = $orders->find($id);
    if ($order === null) {
      $this->throwResponse($response->withJson([
        'status' => 'error',
        'reason' => 'not-found',
      ]));
    }

    if ($token !== null && $token !== $order['token']) {
      $this->throwResponse($response->withJson([
        'status' => 'error',
        'reason' => 'wrong-token',
      ]));
    }

    return $order;
  }

  public function order(Request $request, Response $response, array $args) {
    $body = $request->getParsedBody();

    /** @noinspection PhpUnhandledExceptionInspection */
    return DB::connection()->transaction(function () use ($body, $request, $response) {
      $courses = DB::table('courses');
      $orders = DB::table('orders');
      $ordersCourses = DB::table('orders_courses');

      [
        'cart' => $cart,
        'info' => $info,
        'target' => $target,
      ] = $body;

      $cartItems = $courses->whereIn('id', array_keys($cart))->get();
      if (count($cartItems) !== count($cart)) {
        return $response->withJson([
          'status' => 'change_count',
        ]);
      }

      $price = 0;
      foreach ($cartItems as $item) {
        $count = $cart[$item['id']];
        $price += $count * $item['price'];
      }

      if ($price !== intval($info['price'])) {
        return $response->withJson([
          'status' => 'change_price',
        ]);
      }

      $storageId = $this->storage->getNearest(
        $target['coordinates']['lat'],
        $target['coordinates']['lng']
      );
      $orderId = $orders->insertGetId([
        'contact_name' => $info['name'],
        'phone' => $info['phone'],

        'created_at' => new \DateTime(),
        'price' => $price,

        'address' => $target['address'],
        'latitude' => $target['coordinates']['lat'],
        'longtitude' => $target['coordinates']['lng'],
        'notes' => $info['notes'],

        'token' => $token = str_random(64),

        'storage_id' => $storageId,
      ]);

      $ordersCourses->insert($cartItems->map(function ($item) use ($cart, $orderId) {
        return [
          'order_id' => $orderId,
          'course_id' => $item['id'],
          'count' => $cart[$item['id']],
        ];
      })->toArray());

      $coursesIngredients = $this->db->table('courses_ingredients')
        ->whereIn('course_id', array_keys($cart))
        ->get()
        ->groupBy(['course_id', 'ingredient_id'])
        ->map(function (Collection $ingredients) {
          return $ingredients->map(function (Collection $ingredient) {
            return $ingredient->first()['amount'];
          });
        })
      ;
      $uniqueIngredients = $coursesIngredients->map(function (Collection $ingredients) {
        return $ingredients->keys();
      })->values()->collapse()->unique();
//      $ingredients = $this->db->table('ingredients')
//        ->whereIn('id', $uniqueIngredients)
//        ->get()
//        ->pluck()

//      throw new ResponseException($response->withJson(
//
//      , 500));
      $ingredients = $uniqueIngredients->mapWithKeys(function ($id) {
        return [$id => 0];
      });
      foreach ($cartItems as $item) {
        $id = intval($item['id']);
        $count = $cart[$id];

        foreach ($coursesIngredients[$id] as $ingredient => $amount) {
          $ingredients[$ingredient] += $count * $amount;
        }
      }

      foreach ($ingredients as $id => $count) {
        $result = $this->db->select(
          <<<SQL
SELECT
  *
FROM 
  (SELECT
    *
  FROM
    `storages_batches`
  WHERE
    `remaining` <> 0 AND
    `ingredient_id` = :ingredient AND
    `best_by` >= CURRENT_TIMESTAMP()
  ORDER BY
    `best_by`
  ) r1
JOIN
  (SELECT @rn := 0) r2
WHERE
  (@rn := @rn + `remaining`) - `remaining` < :count
SQL
        , [
          'ingredient' => $id,
          'count' => $count,
        ]);

        foreach ($result as $batch) {
          $taking = min($count, floatval($batch['remaining']));
          $count -= $taking;
          $this->db->update(
            <<<SQL
UPDATE
  `storages_batches`
SET
  `remaining`=`remaining`-:taking
WHERE
  `id`=:id
SQL
          , [
            'id' => $batch['id'],
            'taking' => $taking,
          ]);
        }
      }

      return $response->withJson([
        'status' => 'ok',
        'order_id' => $orderId,
        'token' => $token,
      ]);
    }, 5);
  }

  public function watch(Request $request, Response $response, array $args) {
    ['id' => $id, 'token' => $token] = $args;
    $order = $this->getOrder($response, $id, $token);

    $result = [
      'status' => OrderStatus::toString(intval($order['status'])),

      'needsReview' => $order['review_id'] === null,
    ];

    if (intval($order['status']) === OrderStatus::INROAD) {
      $driverId = intval($order['driver_id']);
      $user = DB::table('users')->where('id', $driverId)->first(['username']);
      $driver = DB::table('drivers')->where('driver_id', $driverId)->first();
      if (null === $driver) {
        $order['status'] = OrderStatus::UNKNOWN;
      } else {
        $result += [
          'driver' => [
            'id' => $driverId,
            'name' => $user['username'],
            'lat' => floatval($driver['latitude']),
            'lng' => floatval($driver['longitude']),
          ],
          'target' => [
            'address' => $order['address'],
            'lat' => floatval($order['latitude']),
            'lng' => floatval($order['longtitude']),
          ],
          'totalDistance' => $order['total_distance'],
        ];
      }
    }

    return $response->withJson($result);
  }

  public function rate(Request $request, Response $response, array $args) {
    $user = $this->getUser($request);

    ['id' => $id, 'token' => $token] = $args;
    $order = $this->getOrder($response, $id, $token);

    $text = $request->getParsedBodyParam('text');
    $rating = $request->getParsedBodyParam('rating');

    if ($order['review_id'] !== null) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'already',
      ]);
    }

    $id = $this->reviews->createReview([
      'source' => [
        'type' => 'order',
        'id' => $id,
      ],

      'target' => [
        'type' => 'driver',
        'id' => $order['driver_id'],
      ],

      'user_id' => $user ? $user['id'] : null,

      'text' => $text,
      'rating' => $rating,
    ]);

    DB::table('orders')->where('id', $order['id'])->update([
      'review_id' => $id,
    ]);

    return $response->withJson([
      'status' => 'ok',
      'id' => $id,
    ]);
  }

  public function dontRate(Request $request, Response $response, array $args) {
    ['id' => $id, 'token' => $token] = $args;
    $order = $this->getOrder($response, $id, $token);

    DB::table('orders')->where('id', $order['id'])->update([
      'review_id' => 0,
    ]);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }
}
