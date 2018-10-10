<?php

namespace App\Resources;

use App\Services\ResourcesService;
use App\Util\DriverStatus;
use Illuminate\Database\Connection;
use Slim\Container;

class DriverResourceProvider extends ResourceProvider {
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

  public function get(int $id): array {
    return $this->db->table('drivers')->where('driver_id', $id)->first();
  }

  public function fromDB(array $original): array {
    return [
      'id' => intval($original['driver_id']),
      'latitude' => $original['latitude'] ? floatval($original['latitude']) : null,
      'longitude' => $original['longitude'] ? floatval($original['longitude']) : null,
      'status' => DriverStatus::toString(intval($original['status'] ?? -1)),
      'order_id' => $original['order_id'] ? intval($original['order_id']) : null,
    ];
  }
}
