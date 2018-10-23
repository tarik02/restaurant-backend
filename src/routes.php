<?php

use App\Controllers\CookController;
use App\Controllers\DefaultController;
use App\Controllers\DriverController;
use App\Controllers\NotificationController;
use App\Controllers\OperatorController;
use App\Controllers\OrderController;
use App\Controllers\ReviewsController;
use App\Controllers\StatsController;
use App\Controllers\StorageController;
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

function rad(float $x) {
  return $x * M_PI / 180;
}
function distanceBetweenTwoPoints($p1, $p2) {
  $R = 6378137; // Earth’s mean radius in meter

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
    $app->get('/notifications', NotificationController::class.':getAndFlush');

  });

  $app->group('', function () use ($app) { // default
    $app->get('/courses', DefaultController::class.':courses');

  });

  $app->group('/order', function () use ($app) { // order
    $app->post('', OrderController::class.':order');
    $app->get('/{id}/{token}', OrderController::class.':watch');
    $app->post('/rate/{id}/{token}', OrderController::class.':rate');
    $app->post('/dont-rate/{id}/{token}', OrderController::class.':dontRate');

  });

  $app->group('/reviews', function () use ($app) { // reviews
    $app->get('', ReviewsController::class.':get');

  });

  $app->group('/storage', function () use ($app) { // storage
    $app->get('', StorageController::class.':all');
    $app->get('/{id}', StorageController::class.':get');
    $app->put('', StorageController::class.':save');
    $app->delete('', StorageController::class.':delete');

    $app->get('/{id}/batches', StorageController::class.':getBatches');
    $app->put('/{storage}/batches[/{id}]', StorageController::class.':putBatch');
    $app->delete('/{storage}/batches/{id}', StorageController::class.':deleteBatch');

    $app->get('/batches/old', StorageController::class.':getOldBatches');

  });

  $app->group('/cook', function () use ($app) { // cook
    $app->get('/dashboard', CookController::class.':dashboard');

    $app->post('/start-cooking/{order_id}/{course_id}', CookController::class.':startCooking');
    $app->post('/cancel-cooking/{id}', CookController::class.':cancelCooking');
    $app->post('/done-cooking/{id}', CookController::class.':doneCooking');

  });

  $app->group('/stats', function () use ($app) { // stats
    $app->get('/courses', StatsController::class.':courses');
    $app->get('/income', StatsController::class.':income');
    $app->get('/ingredients', StatsController::class.':ingredients');
    $app->get('/ingredients/outdated', StatsController::class.':ingredientsOutdated');

  });

  $app->group('/driver', function () use ($app) { // driver
    $app->get('/dashboard', DriverController::class.':dashboard');
    $app->post('/report-location', DriverController::class.':reportLocation');

    $app->post('/do-order/{id}', DriverController::class.':doOrder');

    $app->post('/cancel-order', DriverController::class.':cancelOrder');
    $app->post('/end-order', DriverController::class.':endOrder');

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
