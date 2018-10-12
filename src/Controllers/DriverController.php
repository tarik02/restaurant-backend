<?php

namespace App\Controllers;

use App\Services\ResourcesService;
use App\Util\DriverStatus;
use App\Util\OrderStatus;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class DriverController extends Controller {
  /** @var Connection */
  private $db;

  /** @var ResourcesService */
  private $resources;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->db = $container['db']->connection();
    $this->resources = $container['resources'];
  }

  public function getDriver(int $id): array {
    $driver = $this->resources->getOptional('driver', $id);
    if ($driver === null) {
      $this->db->table('drivers')
        ->insert([
          'driver_id' => $id,
          'status' => DriverStatus::READY,
        ]);
    }

    return $this->resources->get('driver', $id);
  }

  public function dashboard(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'driver');
    $user = $this->getUser($request);
    $driver = $this->getDriver(intval($user['id']));

    $result = [
      'status' => $driver['status'],
    ];
    switch ($driver['status']) {
      case 'ready':
        $orders = $this->db->table('orders')
          ->where('status', OrderStatus::WAITING_FOR_DRIVER)
          ->orderBy('created_at', 'asc')
          ->get()
        ;

        $courses = $this->db->table('orders_courses')
          ->whereIn('order_id', $orders->pluck('id'))
          ->get()
          ->mapToGroups(function (array $course) {
            return [
              intval($course['order_id']) => [
                intval($course['course_id']) => intval($course['count']),
              ],
            ];
          })
          ->map(function (Collection $group) {
            return $group->mapWithKeys(function (array $it) {
              return $it;
            });
          })
        ;

        $result['orders'] = $orders->map(function (array $order) use ($courses) {
          $order = $this->resources->getResourceProvider('order')->fromDB($order);

          return array_merge(
            $order,
            [
              'courses' => $courses[$order['id']],
            ]
          );
        });
        break;
      case 'driving':
        $result['driver'] = $driver;
        $result['order'] = $this->resources->get('order', $driver['order_id']);
        break;
    }

    return $response->withJson($result);
  }

  public function reportLocation(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'driver');
    $user = $this->getUser($request);

    $this->assert(
      $response,
      $location = $request->getParsedBodyParam('location', null)
    );
    $this->assert($response, $lat = floatval($location['latitude']));
    $this->assert($response, $lng = floatval($location['longitude']));

    DB::table('drivers')
      ->updateOrInsert([
        'driver_id' => $user['id'],
      ], [
        'latitude' => $lat,
        'longitude' => $lng,
      ]);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }

  public function doOrder(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'driver');
    $user = $this->getUser($request);

    $id = $args['id'] ?? null;
    $this->assert($response, $id);

    /** @noinspection PhpUnhandledExceptionInspection */
    return $this->db->transaction(function () use ($user, $response, $id) {
      $driver = $this->getDriver($user['id']);
      if ($driver === null || $driver['latitude'] === null || $driver['longitude'] === null) {
        return $response->withJson([
          'status' => 'error',
          'reason' => 'no-location',
        ]);
      }

      $order = $this->db->table('orders')->find($id);

      if (intval($order['status']) !== OrderStatus::WAITING_FOR_DRIVER) {
        return $response->withJson([
          'status' => 'error',
          'reason' => 'not-for-driver',
        ]);
      }

      $totalDistance = distanceBetweenTwoPoints(
        [
          'lat' => floatval($driver['latitude']),
          'lng' => floatval($driver['longitude']),
        ], [
          'lat' => floatval($order['latitude']),
          'lng' => floatval($order['longtitude']),
        ]
      );

      $this->db->table('orders')
        ->where('id', $id)
        ->update([
          'status' => OrderStatus::INROAD,
          'driver_id' => $user['id'],
          'total_distance' => $totalDistance,
        ]);

      $this->db->table('drivers')
        ->where('driver_id', $user['id'])
        ->update([
          'status' => DriverStatus::DRIVING,
          'order_id' => $id,
        ]);

      return $response->withJson([
        'status' => 'ok',
      ]);
    }, 5);
  }

  public function cancelOrder(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'driver');
    $user = $this->getUser($request);
    $driver = $this->getDriver(intval($user['id']));

    if ($driver['order_id'] === null) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'no-order',
      ]);
    }

    $order = $this->resources->get('order', $driver['order_id']);
    $this->db->table('orders')
      ->where('id', $order['id'])
      ->update([
        'status' => OrderStatus::CANCELLED,
      ]);
    $this->db->table('drivers')
      ->where('driver_id', $driver['id'])
      ->update([
        'status' => DriverStatus::READY,
        'order_id' => null,
      ]);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }

  public function endOrder(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'driver');
    $user = $this->getUser($request);
    $driver = $this->getDriver(intval($user['id']));

    if ($driver['order_id'] === null) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'no-order',
      ]);
    }

    $order = $this->resources->get('order', $driver['order_id']);
    $this->db->table('orders')
      ->where('id', $order['id'])
      ->update([
        'status' => OrderStatus::DONE,
      ]);
    $this->db->table('drivers')
      ->where('driver_id', $driver['id'])
      ->update([
        'status' => DriverStatus::READY,
        'order_id' => null,
      ]);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }
}
