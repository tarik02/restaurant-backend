<?php

use App\Commands\MakeMigrationCommand;
use App\Commands\RoutesListCommand;
use Phpmig\Console\Command;
use Symfony\Component\Console\Application;

require_once 'app.php';

$application = new Application();

$application->addCommands([
  new MakeMigrationCommand(),
  new RoutesListCommand($app),
]);

$application->addCommands([
  new Command\InitCommand(),
  new Command\StatusCommand(),
  new Command\CheckCommand(),
  new Command\GenerateCommand(),
  new Command\UpCommand(),
  new Command\DownCommand(),
  new Command\MigrateCommand(),
  new Command\RollbackCommand(),
  new Command\RedoCommand(),
]);

$application->run();
