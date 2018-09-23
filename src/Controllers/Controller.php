<?php

namespace App\Controllers;

use App\Exceptions\ResponseException;
use App\Services\UsersService;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class Controller {
  /** @var UsersService */
  private $users;

  public function __construct(Container $container) {
    $this->users = $container['users'];
  }

  protected function throwResponse(Response $response) {
    throw new ResponseException($response);
  }

  protected function getUser(Request $request): ?array {
    return $this->users->getUserFromRequest($request);
  }

  protected function assertUser(Request $request, Response $response): array {
    if (null === $user = $this->getUser($request)) {
      $this->throwResponse($response->withStatus(401, 'Not authenticated'));
    }

    return $user;
  }

  protected function checkRole(Request $request, string $role): bool {
    if (null === $user = $this->getUser($request)) {
      return false;
    }

    return in_array($role, $user['roles']);
  }

  protected function assertRole(Request $request, Response $response, string $role) {
    $user = $this->assertUser($request, $response);

    if (!in_array($role, $user['roles'])) {
      $this->throwResponse($response->withStatus(401, 'Unaccessible'));
    }
  }
}
