<?php

namespace App\Services;

use Slim\Container;

class RolesService {
  public function __construct(Container $container) {
  }

  public function checkAbility(array $user, string $name) {
    // TODO: check real

    return in_array($name, $user['roles']);
  }
}
