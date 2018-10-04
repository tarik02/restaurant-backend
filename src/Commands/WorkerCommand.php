<?php

namespace App\Commands;

use App\Services\SchedulerWorker;
use Slim\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Command {
  /** @var App */
  private $app;

  /** @var SchedulerWorker */
  private $worker;

  public function __construct(App $app) {
    parent::__construct();

    $this->app = $app;
    $this->worker = $this->app->getContainer()->get('scheduler.worker');
  }

  protected function configure() {
    $this
      ->setName('worker')
      ->setDescription('Worker')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->worker->run();
  }
}
