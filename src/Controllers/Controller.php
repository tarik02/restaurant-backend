<?php

namespace App\Controllers;

use App\Exceptions\ResponseException;
use App\Services\RolesService;
use App\Services\UsersService;
use Illuminate\Database\Query\Builder;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class Controller {
  /** @var UsersService */
  private $users;

  /** @var RolesService */
  private $roles;

  public function __construct(Container $container) {
    $this->users = $container['users'];
    $this->roles = $container['roles'];
  }


  protected function throwResponse(Response $response) {
    throw new ResponseException($response);
  }

  protected function throwBadRequest(Response $response, string $reason = 'Bad Request') {
    $this->throwResponse($response->withStatus(400, $reason));
  }

  protected function assert(Response $response, $condition, string $reason = 'Bad Request') {
    if (!$condition) {
      $this->throwBadRequest($response, $reason);
    }
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

  protected function checkAbility(Request $request, string $name): bool {
    if (null === $user = $this->getUser($request)) {
      return false;
    }

    return $this->roles->checkAbility($user, $name);
  }

  protected function assertAbility(Request $request, Response $response, string $name) {
    $user = $this->assertUser($request, $response);

    if (!$this->roles->checkAbility($user, $name)) {
      $this->throwResponse($response->withStatus(401, 'Unaccessible'));
    }
  }
}
