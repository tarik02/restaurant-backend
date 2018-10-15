<?php

use App\Util\Serializer;
use Illuminate\Database\Capsule\Manager as DB;
use App\Services\NotificationsService;
use Illuminate\Database\Query\Builder;

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
    ->where('best_by', '<', (new DateTime())->add(new DateInterval('P10D'))->format('Y-m-d H:i:s'))
    ->whereNotIn('id', function (Builder $query) {
      $query->select('id')->from('storages_batches_old');
    })
    ->orderBy('best_by')
    ->get();

  if ($batches->isNotEmpty()) {
    DB::table('storages_batches_old')->insert(
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

    // TODO: notify particular users
    $notifications->notify(1, 'storage-batches-old', $notificationBatches->toArray());
  }
})->everyMinute();

/*------------------------------------------------------------------------*/
