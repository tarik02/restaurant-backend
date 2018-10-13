<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Slim\Container;

class StorageService {
  /** @var Connection */
  private $db;

  public function __construct(Container $container) {
    $this->db = $container['db']->connection();
  }

  public function getNearest(float $latitude, float $longitude): int {
    return intval($this->db
      ->table('storages')
      ->select(
        $this->db->raw('`id`, geoDistance(?, ?, `latitude`, `longitude`) AS `distance`')
      )
      ->setBindings([$latitude, $longitude])
      ->orderBy('distance')
      ->first()['id']
    );
  }
}
