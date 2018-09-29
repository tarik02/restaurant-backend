<?php

namespace App\Controllers;

use App\Util\Deserializer;
use App\Util\Filterer;
use App\Util\Serializer;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;

class StorageController extends Controller {
  /** @var Serializer */
  private $serializer;

  /** @var Deserializer */
  private $deserializer;

  /** @var Filterer */
  private $filterer;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->serializer = $container['serializer'];
    $this->deserializer = $container['deserializer'];
    $this->filterer = $container['filterer'];
  }

  public function all(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $page = intval($request->getParam('page', 1));
    $perPage = clamp(intval($request->getParam('perPage', 15)), 5, 100);

    $query = DB::table('storages');
    $query->forPage($page, $perPage);

    $total = $query->getCountForPagination();
    $storages = $query->get();

    return $response->withJson([
      'data' => $storages->map(function (array $storage) {
        return [
          'id' => intval($storage['id']),
          'name' => $storage['name'],

          'location' => [
            'latitude' => floatval($storage['latitude']),
            'longitude' => floatval($storage['longitude']),
          ],
        ];
      }),

      'meta' => [
        'page' => $page,
        'perPage' => $perPage,
        'totalCount' => $total,
      ],
    ]);
  }

  public function get(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $id = $args['id'];
    $storage = DB::table('storages')->find($id);

    return $response->withJson([
      'data' => [
        'id' => intval($storage['id']),
        'name' => $storage['name'],

        'location' => [
          'latitude' => floatval($storage['latitude']),
          'longitude' => floatval($storage['longitude']),
        ],
      ],
    ]);
  }

  public function save(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $table = DB::table('storages');

    $body = $request->getParsedBody();
    $id = $body['id'] ?? null;

    $data = [
      'name' => $body['name'],
      'latitude' => $body['location']['latitude'],
      'longitude' => $body['location']['longitude'],
    ];
    if ($id === null) {
      $id = $table->insertGetId($data);
    } else {
      $table->where('id', $id)->update($data);
    }

    return $response->withJson([
      'status' => 'ok',
      'id' => $id,
    ]);
  }

  public function delete(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $id = intval($request->getParam('id'));
    if ($id === 0) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'bad_request',
      ]);
    }

    $table = DB::table('storages');

    if ($table->delete($id) !== 1) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'not_exist',
      ]);
    }

    DB::table('storages_batches')->where('storage_id', $id);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }


  public function getBatches(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $id = $args['id'];

    $storage = DB::table('storages')->find($id, ['id']);
    if ($storage === null) {
      $this->throwBadRequest($response);
    }

    $sortBy = $request->getParam('sortBy', 'best_by');
    $descending = $request->getParam('descending', 'false') === 'true';

    if (!in_array($sortBy, ['remaining', 'best_by'])) {
      return $response->withStatus(500);
    }

    $page = intval($request->getParam('page', 1));
    $perPage = clamp(intval($request->getParam('perPage', 15)), 5, 100);

    $filter = $request->getParam('filter', []);

    $query = DB::table('storages_batches')->where('storage_id', $storage['id']);
    $query = $this->filterer->filter($query, $filter);
    $query = $query->orderBy($sortBy, $descending ? 'desc' : 'asc');
    $query = $query->forPage($page, $perPage);

    $total = $query->getCountForPagination();
    $batches = $query->get();

    return $response->withJson([
      'data' => $batches->map(function (array $batch) {
        return [
          'id' => intval($batch['id']),

          'ingredient_id' => intval($batch['ingredient_id']),

          'count' => floatval($batch['count']),
          'remaining' => floatval($batch['remaining']),

          'best_by' => $this->serializer->dateTime($batch['best_by']),
        ];
      }),

      'meta' => [
        'page' => $page,
        'perPage' => $perPage,
        'totalCount' => $total,
      ],
    ]);
  }

  public function putBatch(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $table = DB::table('storages_batches');

    $storageId = $args['storage'];
    $id = $args['id'] ?? null;
    $body = $request->getParsedBody();

    $data = [
      'storage_id' => $storageId,
      'ingredient_id' => $body['ingredient_id'],
      'count' => $body['count'],
      'remaining' => $body['remaining'],
      'best_by' => $this->deserializer->dateTime($body['best_by'])->getTimestamp(),
    ];
    if ($id === null) {
      $id = $table->insertGetId($data);
    } else {
      $table->where('id', $id)->update($data);
    }

    return $response->withJson([
      'status' => 'ok',
      'id' => $id,
    ]);
  }

  public function deleteBatch(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $id = intval($args['id']);
    if ($id === 0) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'bad_request',
      ]);
    }

    $table = DB::table('storages_batches');

    if ($table->delete($id) !== 1) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'not_exist',
      ]);
    }

    return $response->withJson([
      'status' => 'ok',
    ]);
  }
}
