<?php

namespace App\Controllers;

use App\Util\Format;
use Slim\Http\Request;
use Slim\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;

class StorageController extends Controller {
  public function all(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'storage');

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
    $this->assertRole($request, $response, 'storage');

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
    $this->assertRole($request, $response, 'storage');

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
    $this->assertRole($request, $response, 'storage');

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

    return $response->withJson([
      'status' => 'ok',
    ]);
  }


  public function getBatches(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'storage');

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

    $query = DB::table('storages_batches')->where('storage_id', $storage['id']);
    $query = $query->orderBy($sortBy, $descending ? 'desc' : 'asc');
    $query = $query->forPage($page, $perPage);

    $total = $query->getCountForPagination();
    $batches = $query->get();

    return $response->withJson([
      'data' => $batches->map(function (array $batch) {
        return [
          'ingredient_id' => intval($batch['ingredient_id']),

          'count' => floatval($batch['count']),
          'remaining' => floatval($batch['remaining']),

          'best_by' => Format::dateTime($batch['best_by']),
        ];
      }),

      'meta' => [
        'page' => $page,
        'perPage' => $perPage,
        'totalCount' => $total,
      ],
    ]);
  }
}
