<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Slim\Container;

class NotificationsService {
  public function __construct(Container $container) {
  }

  public function notify(
    int $userId,
    string $type,
    array $data,
    ?\DateTimeInterface $createdAt = null
  ) {
    if ($createdAt === null) {
      $createdAt = new \DateTime();
    }

    $id = DB::table('notifications')
      ->insertGetId([
        'user_id' => $userId,
        'data' => [
          'type' => $type,
          'data' => $data,
        ],
        'created_at' => $createdAt->getTimestamp(),
      ]);

    return $id;
  }

  public function get(int $userId) {
    $notifications = DB::table('notifications')
      ->where('user_id', $userId)
      ->get();

    return $notifications;
  }

  public function flush(int $userId) {
    $notifications = $this->get($userId);

    $ids = $notifications->pluck('id');
    DB::table('notifications')->whereIn('id', $ids)->delete();

    return $notifications;
  }
}
