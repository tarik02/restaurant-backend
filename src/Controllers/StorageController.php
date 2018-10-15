<?php

namespace App\Controllers;

use App\Util\Deserializer;
use App\Util\Filterer;
use App\Util\Orderer;
use App\Util\Paginator;
use App\Util\Serializer;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

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
            'address' => $storage['address'],
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
          'address' => $storage['address'],
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
      'address' => $body['location']['address'],
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

    $query = DB::table('storages_batches')->where('storage_id', $storage['id']);
    $query = $this->filterer->filter($query, $request->getParam('filter', []));

    $order = (new Orderer())
      ->allow('remaining')
      ->allow('best_by')
      ->by('best_by')
      ->by($request->getParam('sortBy'))
      ->descending($request->getParam('descending', 'false') === 'true')
      ->apply($response, $query);

    $pagination = (new Paginator())
      ->minPerPage(5)
      ->maxPerPage(100)
      ->page(intval($request->getParam('page', 1)))
      ->perPage(intval($request->getParam('perPage', 15)))
      ->apply($response, $query);

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
        'totalCount' => $total,

        'order' => $order,
        'pagination' => $pagination,
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
      'best_by' => $this->deserializer->dateTime($body['best_by'])->format('Y-m-d H:i:s'),
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

    DB::table('storages_batches_old')->delete($id);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }


  public function getOldBatches(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'storage');

    $query = DB::table('storages_batches')
      ->whereIn('id', function (Builder $builder) {
        $builder->select('id')->from('storages_batches_old');
      });
    $query = $this->filterer->filter($query, $request->getParam('filter', []));

    $order = (new Orderer())
      ->allow('remaining')
      ->allow('best_by')
      ->by('best_by')
      ->by($request->getParam('sortBy'))
      ->descending($request->getParam('descending', 'false') === 'true')
      ->apply($response, $query);

    $pagination = (new Paginator())
      ->minPerPage(5)
      ->maxPerPage(100)
      ->page(intval($request->getParam('page', 1)))
      ->perPage(intval($request->getParam('perPage', 15)))
      ->apply($response, $query);

    $total = $query->getCountForPagination();
    $batches = $query->get();

    $storageIds = $batches->pluck('storage_id')->map('intval')->unique();
    $storages = DB::table('storages')
      ->whereIn('id', $storageIds)
      ->get()
      ->pluck('name', 'id');

    return $response->withJson([
      'data' => $batches->map(function (array $batch) use ($storages) {
        $storageId = intval($batch['storage_id']);

        return [
          'id' => intval($batch['id']),

          'storage' => [
            'id' => $storageId,
            'name' => $storages[$storageId],
          ],
          'ingredient_id' => intval($batch['ingredient_id']),

          'count' => floatval($batch['count']),
          'remaining' => floatval($batch['remaining']),

          'best_by' => $this->serializer->dateTime($batch['best_by']),
        ];
      }),

      'meta' => [
        'totalCount' => $total,

        'order' => $order,
        'pagination' => $pagination,
      ],
    ]);
  }
}
