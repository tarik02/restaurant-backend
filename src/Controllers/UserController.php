<?php

namespace App\Controllers;

use App\Services\UsersService;
use Chadicus\Slim\OAuth2\Middleware\Authorization;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController {
  /** @var Container */
  protected $container;

  /** @var UsersService */
  protected $users;

  public function __construct(Container $container) {
    $this->container = $container;
    $this->users = $container['users'];
  }

  public function register(Request $request, Response $response, array $args) {
    $body = $request->getParsedBody();

    if ($this->users->exists($body['username'], $body['email'])) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'already_exists',
      ]);
    }

    $id = $this->users->register([
      'username' => $body['username'],
      'email' => $body['email'] ?? null,
      'phone' => $body['phone'] ?? null,
      'password' => $body['password'],
      'roles' => ['user'],
    ]);

    return $response->withJson([
      'status' => 'ok',
      'id' => $id,
    ]);
  }

  public function user(Request $request, Response $response, array $args) {
    $user = $this->users->getUserFromRequest($request);

    return $response->withJson([
      'id' => $user['id'],
      'username' => $user['username'],
      'email' => $user['email'],
      'phone' => $user['phone'],
      'avatar' => $user['avatar'],
      'roles' => $user['roles'],
    ]);
  }
}
