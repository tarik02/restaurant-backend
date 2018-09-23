<?php

namespace App\Middleware;

use App\Exceptions\ResponseException;
use Slim\Http\Request;
use Slim\Http\Response;

class ResponseExceptionMiddleware {
  public function __invoke(Request $request, Response $response, callable $next) {
    try {
      return $next($request, $response);
    } catch (ResponseException $exception) {
      return $exception->getResponse();
    }
  }
}
