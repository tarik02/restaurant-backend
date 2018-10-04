<?php

namespace App\Services;

use App\Tasks\Task;
use GO\Job;
use Illuminate\Database\Capsule\Manager as DB;
use GO\Scheduler;
use Illuminate\Database\QueryException;
use Monolog\Handler\PHPConsoleHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Slim\Container;

class SchedulerWorker {
  /** @var Container */
  private $container;

  /** @var Logger */
  private $logger;

  /** @var Scheduler */
  private $scheduler;

  /** @var bool */
  private $running = false;

  /** @var int */
  private $nextSchedulerRun = 0;

  public function __construct(Container $container) {
    $this->container = $container;
    $this->logger = $container['logger'];

    $this->scheduler = new Scheduler();

    $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

    (function (Container $container, Scheduler $scheduler) {
      include __DIR__ . '/../scheduler.php';
    })($container, $this->scheduler);
  }

  public function update() {
    if (($ts = (new \DateTime())->getTimestamp()) > $this->nextSchedulerRun) {
      $this->nextSchedulerRun = $ts + 60;
      $this->scheduler->run();

      /** @var Job $job */
      foreach ($this->scheduler->getExecutedJobs() as $job) {
        $output = $job->getOutput();

        $this->logger->info('Executed job');
        $this->logger->info($output);
      }

      /** @var Job $job */
      foreach ($this->scheduler->getFailedJobs() as $job) {
        $output = $job->getOutput();

        $this->logger->error('Failed job');
        $this->logger->error($output);
      }

      foreach ($this->scheduler->getVerboseOutput('array') as $row) {
        $this->logger->error($row);
      }

      $this->scheduler->resetRun();
    }

    while (true) {
      try {
        /** @noinspection PhpUnhandledExceptionInspection */
        $taskData = DB::connection()->transaction(
          function () {
            $taskData = DB::table('tasks')
              ->where('status', 0)
              ->orderBy('next_run', 'asc')
              ->first(['id', 'data']);

            if ($taskData === null) {
              return null;
            }

            DB::table('tasks')
              ->where('id', $taskData['id'])
              ->update(
                [
                  'status' => 1,
                ]
              );

            return $taskData;
          }
        );

        if ($taskData === null) {
          break;
        }

        /** @var Task $task */
        $task = unserialize(
          $taskData['data']
        );

        try {
          $this->logger->info(
            sprintf(
              'Executing task "%s"...',
              get_class($task)
            )
          );
          $task->run();
          $this->logger->info(
            sprintf(
              'Task "%s" executed.',
              get_class($task)
            )
          );
        } catch (\Throwable $e) {
          $this->logger->error(
            sprintf(
              'Task "%s" failed.',
              get_class($task)
            )
          );
          $this->logger->error((string)$e);
        }

        DB::table('tasks')
          ->where('id', $taskData['id'])
          ->delete();
      } catch (QueryException $exception) {
        break;
      }
    }
  }

  public function run() {
    if ($this->running) {
      return;
    }

    $this->running = true;
    while ($this->running) {
      $start = microtime(true);
      $this->update();
      $end = microtime(true);

      $diff = ($end - $start) / 1000000;

      if ($diff < 20) {
        usleep((int) ((20 - $diff) * 1000));
      }
    }
  }
}
