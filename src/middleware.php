<?php

use App\Middleware\ResponseExceptionMiddleware;
use App\Middleware\UserMiddleware;
use Slim\Http\Response;
use Slim\Http\Request;
use Tuupola\Middleware\CorsMiddleware;

// Application middleware

/** @var \Slim\Container $container */
$container = $app->getContainer();

$app->add(new CorsMiddleware(array_merge(
  $app->getContainer()['settings']['cors'],
  [
//    'logger' => $container->get('logger')->withName('cors'),
  ]
)));

$server = $container['oauth2-server'];
$authMiddleware = new \Chadicus\Slim\OAuth2\Middleware\Authorization($server, $container, [null]);
$app->add(function (Request $request, Response $response, callable $next) use ($authMiddleware) {
  if (
    $request->getMethod() === 'OPTIONS'
    || !$request->hasHeader('Authorization')
  ) {
    return $next($request, $response);
  }

  return $authMiddleware($request, $response, $next);
});

$app->add(function(Request $request, Response $response, callable $next) {
  $this->get('db'); // Connect to Database

  return $next($request, $response);
});

$app->add(new ResponseExceptionMiddleware());
$app->add(new UserMiddleware($container));
