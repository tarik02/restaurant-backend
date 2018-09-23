<?php

use App\Middleware\ResponseExceptionMiddleware;
use Slim\Http\Response;
use Slim\Http\Request;
use Tuupola\Middleware\CorsMiddleware;

// Application middleware

$app->add(new CorsMiddleware(array_merge(
  $app->getContainer()['settings']['cors'],
  [
//    'logger' => $app->getContainer()->get('logger')->withName('cors'),
  ]
)));

$app->add(function(Request $request, Response $response, callable $next) {
  $this->get('db'); // Connect to Database

  return $next($request, $response);
});

$app->add(new ResponseExceptionMiddleware());
