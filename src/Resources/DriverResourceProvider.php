<?php

namespace App\Resources;

use App\Services\ResourcesService;
use Illuminate\Database\Connection;
use Slim\Container;

class DriverResourceProvider implements ResourceProvider {
  /** @var Connection */
  private $db;

  /** @var ResourcesService */
  private $resources;

  public function __construct(Container $container) {
    $this->db = $container['db']->connection();
    $this->resources = $container['resources'];
  }

  public function get(int $id): array {
    return array_merge(
      $this->resources->get('user', $id),
      []
    );
  }
}
