<?php

use App\Services\MapsService;
use App\Util\Deserializer;
use App\Util\DriverStatus;
use App\Util\OrderStatus;
use App\Util\Serializer;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\NotificationsService;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

/** @var \Slim\Container $container */
/** @var \App\Services\SchedulerWorker $service */
/** @var \GO\Scheduler $scheduler */

/** @var Connection $db */
$db = $container['db']->connection();

/** @var Serializer $serializer */
$serializer = $container['serializer'];

/** @var Deserializer $deserializer */
$deserializer = $container['deserializer'];

/** @var NotificationsService $notifications */
$notifications = $container['notifications'];

/** @var MapsService $maps */
$maps = $container['maps'];

/*------------------------------------------------------------------------*/
/* Check storage batches time */
$scheduler->call(function () use ($db, $serializer, $notifications) {

  $batches = $db->table('storages_batches')
    ->where('remaining', '>', '0')
    ->where('best_by', '<', (new DateTime())->add(new DateInterval('P10D'))->format('Y-m-d H:i:s'))
    ->whereNotIn('id', function (Builder $query) {
      $query->select('id')->from('storages_batches_old');
    })
    ->orderBy('best_by')
    ->get();

  if ($batches->isNotEmpty()) {
    $db->table('storages_batches_old')->insert(
      $batches->pluck('id')->map('intval')->map(
        function (int $id) {
          return ['id' => $id];
        }
      )->toArray()
    );

    $notificationBatches = $batches->map(
      function (array $batch) use ($serializer) {
        return [
          'id' => intval($batch['id']),
          'storage_id' => intval($batch['storage_id']),
          'ingredient_id' => intval($batch['ingredient_id']),
          'count' => floatval($batch['count']),
          'remaining' => floatval($batch['remaining']),
          'best_by' => $serializer->dateTime($batch['best_by']),
        ];
      }
    );

    $ids = $db->table('users')
      ->whereJsonContains('roles', 'storage')
      ->get(['id'])
      ->map(function (array $row) {
        return $row['id'];
      })
      ->map('intval')
      ->toArray()
    ;
    $notifications->broadcast($ids, 'storage-batches-old', $notificationBatches->toArray());
  }
})->everyMinute();
/*------------------------------------------------------------------------*/

/*------------------------------------------------------------------------*/
/* Drivers */
$service->addTicker(function () use ($db, $maps, $deserializer) {
  $db->transaction(function () use ($db, $maps, $deserializer) {
//    $mem = [];
//    $memoize = function () use (&$mem) {
//
//    };

    $convertOrder = function (array $order) use ($deserializer) {
      return [
        'id' => intval($order['id']),
        'contact_name' => $order['contact_name'],
        'phone' => $order['phone'],
        'address' => $order['address'],
        'latitude' => floatval($order['latitude']),
        'longitude' => floatval($order['longtitude']),
        'notes' => $order['notes'],
        'token' => $order['token'],
        'status' => intval($order['status']),
        'driver_id' => (null !== $driverId = $order['driver_id']) ? intval($driverId) : null,
        'total_distance' => floatval($order['total_distance']),
        'created_at' => $deserializer->dateTime($order['created_at']),
        'price' => intval($order['price']),
        'review_id' => intval($order['review_id']),
        'storage_id' => intval($order['storage_id']),
      ];
    };

    $drivers = $db->table('drivers')
      ->whereIn('status', [
        DriverStatus::IDLE,
        DriverStatus::DRIVING,
      ])
      ->get()
      ->map(function (array $driver) {
        return [
          'id' => intval($driver['driver_id']),
          'latitude' => floatval($driver['latitude']),
          'longitude' => floatval($driver['longitude']),
          'status' => intval($driver['status']),
          'order_id' => (null !== $orderId = $driver['order_id']) ? intval($orderId) : null,
        ];
      })
      ->keyBy('id')
      ->toArray()
    ;
    $orders = $db->table('orders')
      ->where('status', OrderStatus::WAITING_FOR_DRIVER)
      ->get()
      ->map($convertOrder)
      ->keyBy('id')
      ->toArray()
    ;
    $driversOrders = $db->table('orders')
      ->whereIn(
        'id',
        collect($drivers)
          ->map(function (array $driver) {
            return $driver['order_id'];
          })
          ->filter(function ($orderId) {
            return $orderId !== null;
          })
      )
      ->get()
      ->map($convertOrder)
      ->keyBy('id')
      ->toArray()
    ;
    $ordersStorages = $db->table('storages')
      ->whereIn(
        'id',
        collect($orders)
          ->map(function (array $order) {
            return $order['storage_id'];
          })
      )
      ->get()
      ->map(function (array $storage) {
        return [
          'id' => intval($storage['id']),
          'name' => $storage['name'],
          'latitude' => floatval($storage['latitude']),
          'longitude' => floatval($storage['longitude']),
          'address' => $storage['address'],
        ];
      })
      ->keyBy('id')
      ->toArray()
    ;

    foreach ($drivers as &$driver) {
      $driver['order'] = (null !== $orderId = $driver['order_id'])
        ? $driversOrders[$orderId] ?? null
        : null;
    }

    foreach ($orders as &$order) {
      $order['driver'] = (null !== $driverId = $order['driver_id'])
        ? $drivers[$driverId]
        : null;
      $order['storage'] = $ordersStorages[$order['storage_id']];
    }

    foreach ($orders as &$order) {
      $storage = $order['storage'];
      $bestKey = null;
      $bestDist = null;

      foreach ($drivers as &$driver) {
        $dist = null;
        if ($driver['status'] === DriverStatus::DRIVING) {
          // TODO: Skip if has in queue
          continue;

          /**
           * Sum of distances:
           *  - from driver to his order
           *  - from his order to storage
           *  - from storage to the order
           */
          $dist =
            $maps->estimatedTravelTime([
              'lat' => $driver['latitude'],
              'lng' => $driver['longitude'],
            ], [
              'lat' => $driver['order']['latitude'],
              'lng' => $driver['order']['longitude'],
            ])
            +
            $maps->estimatedTravelTime([
              'lat' => $driver['order']['latitude'],
              'lng' => $driver['order']['longitude'],
            ], [
              'lat' => $storage['latitude'],
              'lng' => $storage['longitude'],
            ])
            +
            $maps->estimatedTravelTime([
              'lat' => $storage['latitude'],
              'lng' => $storage['longitude'],
            ], [
              'lat' => $order['latitude'],
              'lng' => $order['longitude'],
            ])
          ;
        } else {
          /**
           * Sum of distances:
           *  - from driver to storage
           *  - from storage to the order
           */
          $dist =
            $maps->estimatedTravelTime([
              'lat' => $driver['latitude'],
              'lng' => $driver['longitude'],
            ], [
              'lat' => $storage['latitude'],
              'lng' => $storage['longitude'],
            ])
            +
            $maps->estimatedTravelTime([
              'lat' => $storage['latitude'],
              'lng' => $storage['longitude'],
            ], [
              'lat' => $order['latitude'],
              'lng' => $order['longitude'],
            ])
          ;
        }

        if ($dist !== null && ($bestDist === null || $bestDist > $dist)) {
          $bestDist = $dist;
          $bestKey = $driver['id'];
        }
      }

      if ($bestKey !== null) {
        $driver = &$drivers[$bestKey];
        $totalDistance = distanceBetweenTwoPoints(
          [
            'lat' => $driver['latitude'],
            'lng' => $driver['longitude'],
          ], [
            'lat' => $order['latitude'],
            'lng' => $order['longitude'],
          ]
        );

        $order['status'] = OrderStatus::INROAD;
        $order['driver_id'] = $driver['id'];
        $order['driver'] = $driver;
        $order['total_distance'] = $totalDistance;

        $driver['status'] = DriverStatus::DRIVING;
        $driver['order_id'] = $order['id'];
        $driver['order'] = $order;

        $db->table('orders')
          ->where('id', $order['id'])
          ->update([
            'status' => OrderStatus::INROAD,
            'driver_id' => $driver['id'],
            'total_distance' => $totalDistance,
          ]);

        $db->table('drivers')
          ->where('driver_id', $driver['id'])
          ->update([
            'status' => DriverStatus::DRIVING,
            'order_id' => $order['id'],
          ]);
      }
    }
  }, 5);
});
/*------------------------------------------------------------------------*/
