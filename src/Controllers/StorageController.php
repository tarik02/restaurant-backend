<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;

class StorageController extends Controller {
  public function all(Request $request, Response $response, array $args) {
    $this->assertRole($request, $response, 'storage');

    $storages = DB::table('storages')
      ->get();

    return $response->withJson(
      $storages->map(function (array $storage) {
        return [
          'id' => intval($storage['id']),
          'name' => $storage['name'],

          'location' => [
            'latitude' => floatval($storage['latitude']),
            'longitude' => floatval($storage['longitude']),
          ],
        ];
      })
    );
  }
}
