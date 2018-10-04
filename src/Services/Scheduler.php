<?php

namespace App\Services;

use App\Tasks\Task;
use Cron\CronExpression;
use GO\Job;
use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Slim\Container;

class Scheduler {
  /** @var Container */
  private $container;

  /** @var Logger */
  private $logger;

  /** @var array */
  private $config;

  public function __construct(Container $container, array $config = []) {
    $this->container = $container;
    $this->logger = $container['logger'];

    $this->config = $config;
  }

  public function queueTask(Task $task, \DateTimeInterface $dateTime) {
    DB::table('tasks')
      ->insert(
        [
          'status' => 0,
          'next_run' => $dateTime->getTimestamp(),
          'data' => serialize($task),
        ]
      );
  }
}
