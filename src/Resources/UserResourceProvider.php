<?php

namespace App\Resources;

use Illuminate\Database\Connection;
use Slim\Container;

class UserResourceProvider extends ResourceProvider {
  /** @var Connection */
  private $db;

  public function __construct(Container $container) {
    $this->db = $container['db']->connection();
  }

  public function get(int $id): array {
    return $this->db->table('users')->find($id);
  }

  public function fromDB(array $user): array {
    return [
      'id' => intval($user['id']),
      'username' => $user['username'],
      'avatar' => $user['avatar'],
    ];
  }
}
