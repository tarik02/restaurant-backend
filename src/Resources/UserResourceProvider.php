<?php

namespace App\Resources;

use Illuminate\Database\Connection;
use Slim\Container;

class UserResourceProvider implements ResourceProvider {
  /** @var Connection */
  private $db;

  public function __construct(Container $container) {
    $this->db = $container['db']->connection();
  }

  public function get(int $id): array {
    $user = $this->db->table('users')->find($id);

    return [
      'id' => intval($user['id']),
      'username' => $user['username'],
      'avatar' => $user['avatar'],
    ];
  }
}
