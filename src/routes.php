<?php

use App\Controllers\DefaultController;
use App\Controllers\OperatorController;
use Slim\Http\Request;
use Slim\Http\Response;

// Routes
/** @var \Slim\App $app */

const STR_RANDOM_CHARACTERS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
if (!function_exists('str_random')) {
  function str_random(int $length = 16) {
    $count = strlen(STR_RANDOM_CHARACTERS);

    $out = str_repeat(' ', $length);
    for ($i = $length - 1; $i >= 0; ++$i) {
      $out[$i] = STR_RANDOM_CHARACTERS[mt_rand(0, $count - 1)];
    }

    return $out;
  }
}

function distanceBetweenTwoPoints($p1, $p2) {
  $R = 6378137; // Earth’s mean radius in meter
  function rad(float $x) {
    return $x * M_PI / 180;
  }

  $dLat = rad($p2['lat'] - $p1['lat']);
  $dLng = rad($p2['lng'] - $p1['lng']);

  $a =
    sin($dLat / 2) ** 2 +
    sin($dLng / 2) ** 2 *
    cos(rad($p1['lat'])) * cos(rad($p2['lat']));

  $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
  return $R * $c; // returns the distance in meter
}

function host_path(Request $request, string $path) {
  return $request->getUri()->withPath($path)->withQuery('')->__toString();
}

$app->get('/', function (Request $request, Response $response, array $args) {
  $response->getBody()->write('Nothing to see here...');
  return $response;
});

$app->group('/api/v1', function () use ($app) { //api

  $app->group('', function () use ($app) { // user
    $app->get('/courses', DefaultController::class.':courses');
    $app->post('/order', DefaultController::class.':order');
    $app->get('/order/{id}/{token}', DefaultController::class.':orderWatch');

  });

  $app->group('/operator', function () use($app) { // operator
    $app->post('/orders', OperatorController::class . ':orders');

    $app->get('/courses', OperatorController::class.':courses');
    $app->post('/courses', OperatorController::class.':courseSave');
    $app->post('/courses/remove', OperatorController::class.':courseRemove');

    $app->get('/ingredients', OperatorController::class.':ingredients');
    $app->put('/ingredients', OperatorController::class.':ingredientSave');
    $app->delete('/ingredients', OperatorController::class.':ingredientDelete');

  }); // TODO: middleware
});
