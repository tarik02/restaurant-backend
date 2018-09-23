<?php

namespace App\Commands;

use Slim\App;
use Slim\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoutesListCommand extends Command {
  /** @var App */
  private $app;

  public function __construct(App $app) {
    parent::__construct();

    $this->app = $app;
  }

  protected function configure() {
    $this
      ->setName('routes:list')
      ->setDescription('List Routes')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var Router $router */
    $router = $this->app->getContainer()->get('router');

    foreach ($router->getRoutes() as $route) {
      $output->writeln("{$route->getPattern()} => {$route->getName()}");
    }
  }
}
