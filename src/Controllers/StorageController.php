<?php

namespace App\Controllers;

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
}
