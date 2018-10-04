<?php

use App\Util\Serializer;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\NotificationsService;

/** @var \Slim\Container $container */
/** @var \GO\Scheduler $scheduler */

/** @var Serializer $serializer */
$serializer = $container['serializer'];

/** @var NotificationsService $notifications */
$notifications = $container['notifications'];

/*------------------------------------------------------------------------*/
/* Check storage batches time */
$scheduler->call(function () use ($serializer, $notifications) {

  $batches = DB::table('storages_batches')
    ->where('remaining', '>', '0')
    ->where('best_by', '<', (new DateTime())->add(new DateInterval('P10D'))->getTimestamp())
    ->orderBy('best_by')
    ->get();

  foreach ($batches as $batch) {
    $notifications->notify(1, 'storage-batch-old', [
      'id' => intval($batch['id']),
      'storage_id' => intval($batch['storage_id']),
      'ingredient_id' => intval($batch['ingredient_id']),
      'count' => floatval($batch['count']),
      'remaining' => floatval($batch['remaining']),
      'best_by' => $serializer->dateTime($batch['best_by']),
    ]);
  }

})->everyMinute();

/*------------------------------------------------------------------------*/
