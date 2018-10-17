<?php

namespace App\Resources;

use App\Services\ResourcesService;
use App\Util\DriverStatus;
use Illuminate\Database\Connection;
use Slim\Container;

class CookResourceProvider extends ResourceProvider {
  /** @var Connection */
  private $db;

  /** @var ResourcesService */
  private $resources;

  public function __construct(Container $container) {
    $this->db = $container['db']->connection();
    $this->resources = $container['resources'];
  }

  public function derived(array $resource): array {
    return [
      $this->resources->get('user', $resource['id']),
    ];
  }

  public function get(int $id): ?array {
    return $this->db->table('cooks')->where('user_id', $id)->first();
  }

  public function fromDB(array $original): array {
    return [
      'id' => intval($original['user_id']),
      'storage_id' => intval($original['storage_id']),
    ];
  }
}
