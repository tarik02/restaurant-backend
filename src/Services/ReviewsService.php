<?php

namespace App\Services;

use App\Util\Deserializer;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection;
use Slim\Container;

class ReviewsService {
  /** @var Connection */
  private $db;

  /** @var Deserializer */
  private $deserializer;

  public function __construct(Container $container) {
    $this->db = $container['db']->connection();
    $this->deserializer = $container['deserializer'];
  }

  public function createReview(array $review): int {
    $source = $review['source'] ?? null;
    $target = $review['target'] ?? null;

    return $this->db->table('reviews')->insertGetId(
      [
        'source_type' => $source ? $source['type'] : null,
        'source_id' => $source ? $source['id'] : null,

        'target_type' => $target ? $target['type'] : null,
        'target_id' => $target ? $target['id'] : null,

        'user_id' => $review['user_id'] ?? null,

        'text' => $review['text'] ?? null,
        'rating' => $review['rating'] ?? null,

        'created_at' => !empty($review['created_at'])
          ? $this->deserializer->dateTime($review['created_at'])->getTimestamp()
          : (new \DateTime())->getTimestamp(),
      ]
    );
  }
}
