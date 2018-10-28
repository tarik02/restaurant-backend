<?php

namespace App\Services;

use App\Util\Clock;
use App\Util\Deserializer;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use Slim\Container;

class NotificationsService {
  public function __construct(Container $container) {
  }

  public function notify(
    int $userId,
    string $type,
    array $data,
    ?\DateTimeInterface $createdAt = null
  ): int {
    if ($createdAt === null) {
      $createdAt = Clock::current();
    }

    $id = DB::table('notifications')
      ->insertGetId([
        'user_id' => $userId,
        'data' => json_encode([
          'type' => $type,
          'data' => $data,
        ]),
        'created_at' => $createdAt->format('Y-m-d H:i:s'),
      ]);

    return $id;
  }

  public function broadcast(
    array $userIds,
    string $type,
    array $data,
    ?\DateTimeInterface $createdAt = null
  ): array {
    if ($createdAt === null) {
      $createdAt = Clock::current();
    }

    $table = DB::table('notifications');
    $dataString = json_encode([
      'type' => $type,
      'data' => $data,
    ]);
    $createdAtString = $createdAt->format('Y-m-d H:i:s');

    $results = [];
    foreach ($userIds as $id) {
      $results[$id] = $table->insertGetId([
        'user_id' => $id,
        'data' => $dataString,
        'created_at' => $createdAtString,
      ]);
    }

    return $results;
  }

  public function get(int $userId): Collection {
    $notifications = DB::table('notifications')
      ->where('user_id', $userId)
      ->get();

    return $notifications->map(function (array $notification) {
      $data = json_decode($notification['data'], true);

      return [
        'id' => intval($notification['id']),
        'type' => $data['type'],
        'data' => $data['data'],
        'created_at' => new \DateTime('@' . intval($notification['created_at'])),
      ];
    });
  }

  public function flush(int $userId): Collection {
    $notifications = $this->get($userId);

    $ids = $notifications->pluck('id');
    DB::table('notifications')->whereIn('id', $ids)->delete();

    return $notifications;
  }
}
