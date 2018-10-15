<?php

namespace App\Resources;

use App\Util\OrderStatus;
use App\Util\Serializer;
use Illuminate\Database\Connection;
use Slim\Container;

class OrderResourceProvider extends ResourceProvider {
  /** @var Connection */
  private $db;

  /** @var Serializer */
  private $serializer;

  public function __construct(Container $container) {
    $this->db = $container['db']->connection();
    $this->serializer = $container['serializer'];
  }

  public function get(int $id): ?array {
    return $this->db->table('orders')->find($id);
  }

  public function fromDB(array $order): array {
    return [
      'id' => intval($order['id']),
      'contact_name' => $order['contact_name'],
      'phone' => $order['phone'],

      'target' => [
        'address' => $order['address'],
        'latitude' => floatval($order['latitude']),
        'longitude' => floatval($order['longtitude']),
      ],

      'notes' => $order['notes'],
      'status' => OrderStatus::toString(intval($order['status'])),

      'driver_id' => intval($order['driver_id']),
      'total_distance' => floatval($order['total_distance']),

      'created_at' => $this->serializer->dateTime($order['created_at']),

      'price' => floatval($order['price']),
      'review_id' => intval($order['review_id']),
    ];
  }
}
