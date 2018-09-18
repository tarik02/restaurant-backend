<?php

use App\Services\Uploads;
use Illuminate\Database\Events\QueryExecuted;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Events\StatementPrepared;

// DIC configuration

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
  $settings = $c->get('settings')['logger'];
  $logger = new Monolog\Logger($settings['name']);
//  $logger->pushProcessor(new Monolog\Processor\UidProcessor());
  $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
  return $logger;
};

// Service factory for the ORM
$container['db'] = function ($container) {
  $capsule = new Manager();
  $capsule->addConnection($container['settings']['db']);

  $dispatcher = new Dispatcher();
  $dispatcher->listen(StatementPrepared::class, function (StatementPrepared $event) {
    $event->statement->setFetchMode(PDO::FETCH_ASSOC);
  });
  $dispatcher->listen(QueryExecuted::class, function (QueryExecuted $event) use($container) {
    /** @var \Monolog\Logger $logger */
    $logger = $container->get('logger');
    $logger->debug("Query executed ({$event->time}): {$event->sql}");
    foreach ($event->bindings as $key => $value) {
      if ($value instanceof DateTime) {
        $value = $value->format(DateTime::ISO8601);
      }
      $logger->debug("$key => $value");
    }
  });

  $capsule->setEventDispatcher($dispatcher);
  $capsule->setAsGlobal();
  $capsule->bootEloquent();

  return $capsule;
};

$container['uploads'] = function (ContainerInterface $container) {
  return new Uploads($container);
};

$container['phpErrorHandler'] = $container['errorHandler'] = function ($container) {
  return function (Request $request, Response $response, \Throwable $exception) use ($container) {
    $logger = $container->logger;
    foreach (explode(PHP_EOL, (string) $exception) as $entry) {
      $logger->error(
        $entry
      );
    }

    return $response
      ->withStatus(500)
      ->withJson(['status' => 'error']);
  };
};
