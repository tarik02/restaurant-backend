<?php

use App\Controllers\DefaultController;
use App\Controllers\OperatorController;
use App\Controllers\UserController;
use Chadicus\Slim\OAuth2\Routes;
use Chadicus\Slim\OAuth2\Middleware;
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

  $container = $app->getContainer();
  $server = $container->get('oauth2-server');

  $app->group('/auth', function () use ($app, $container, $server) {
    $views = $container->get('oauth2-views');

    $app->map(['GET', 'POST'], Routes\Authorize::ROUTE, new Routes\Authorize($server, $views))->setName('oauth2.authorize');
    $app->post(Routes\Token::ROUTE, new Routes\Token($server))->setName('oauth2.token');
    $app->map(['GET', 'POST'], Routes\ReceiveCode::ROUTE, new Routes\ReceiveCode($views))->setName('oauth2.receive-code');
    $app->post('/register', UserController::class.':register');
  });

  $app->group('/user', function () use ($app, $server) { // user

    $app->get('', UserController::class.':user');

  });

  $app->group('', function () use ($app) { // default
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

  });
});
