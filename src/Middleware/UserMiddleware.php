<?php

namespace App\Middleware;

use App\Services\UsersService;
use Monolog\Logger;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class UserMiddleware {
  /** @var UsersService */
  private $users;

  /** @var Logger */
  private $logger;

  public function __construct(Container $container) {
    $this->users = $container['users'];
    $this->logger = $container['logger'];
  }

  public function __invoke(Request $request, Response $response, callable $next) {
    if (null !== $user = $this->users->getUserFromRequest($request)) {
      $request = $request->withAttribute(UsersService::ATTRIBUTE_KEY_USER, $user);
    }

    return $next($request, $response);
  }
}
