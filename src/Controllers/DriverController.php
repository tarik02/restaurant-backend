<?php

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Slim\Http\Request;
use Slim\Http\Response;

class DriverController extends Controller {
  public function reportLocation(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'driver');
    $user = $this->getUser($request);

    $this->assert(
      $response,
      $location = $request->getParsedBodyParam('location', null)
    );
    $this->assert($response, $lat = floatval($location['latitude']));
    $this->assert($response, $lng = floatval($location['longitude']));

    DB::table('drivers')
      ->updateOrInsert([
        'driver_id' => $user['id'],
      ], [
        'latitude' => $lat,
        'longitude' => $lng,
      ]);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }
}
