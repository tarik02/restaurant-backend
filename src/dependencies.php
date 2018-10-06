<?php

use App\Services\Uploads;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Events\Dispatcher;
use Phpmig\Adapter;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use SuperClosure\Serializer;

// DIC configuration

$container = $app->getContainer();

// monolog
$container['logger'] = function (Container $container) {
  $settings = $container->get('settings')['logger'];
  $logger = new Monolog\Logger($settings['name']);
//  $logger->pushProcessor(new Monolog\Processor\UidProcessor());
  $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
  return $logger;
};

// Service factory for the ORM
$container['db'] = function (Container $container) {
  $capsule = new Manager();

  $config = $container['settings']['db'];
  $connection = $config['connections'][$config['connection']];
  $capsule->addConnection($connection);
  $capsule->setFetchMode(PDO::FETCH_ASSOC);

  $dispatcher = new Dispatcher();
  $dispatcher->listen(StatementPrepared::class, function (StatementPrepared $event) use ($capsule) {
    $event->statement->setFetchMode($capsule->getContainer()['config']['database.fetch']);
  });
//  $dispatcher->listen(QueryExecuted::class, function (QueryExecuted $event) use($container) {
//    /** @var \Monolog\Logger $logger */
//    $logger = $container->get('logger');
//    $logger->debug("Query executed ({$event->time}): {$event->sql}");
//    foreach ($event->bindings as $key => $value) {
//      if ($value instanceof DateTime) {
//        $value = $value->format(DateTime::ISO8601);
//      }
//      $logger->debug("$key => $value");
//    }
//  });

  $capsule->setEventDispatcher($dispatcher);
  $capsule->setAsGlobal();
  $capsule->bootEloquent();

  return $capsule;
};

$container['users'] = function (Container $container) {
  $container->get('db');

  return new \App\Services\UsersService($container);
};

$container['phpmig.adapter'] = function (Container $container) {
  /** @var Illuminate\Database\Capsule\Manager $capsule */
  $capsule = $container->get('db');
  $capsule->setFetchMode(PDO::FETCH_OBJ); // Adapter needs fetch mode object

  return new Adapter\Illuminate\Database($capsule, 'migrations');
};

$container['phpmig.migrations_path'] = base_path() . '/migrations';
$container['phpmig.migrations_template_path'] = $container['phpmig.migrations_path'] . DIRECTORY_SEPARATOR . '.template.php';

$container['uploads'] = function (Container $container) {
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

$container['oauth2-storage'] = function (Container $container) {
  /** @var Manager $db */
  $db = $container->get('db');
  $pdo = $db->getConnection()->getPdo();

  return new OAuth2\Storage\Pdo($pdo);
};

$container['oauth2-server'] = function (Container $container) {
  $storage = $container->get('oauth2-storage');
  $settings = $container->get('settings');
  $usersService = $container->get('users');

  return new OAuth2\Server(
    $storage,
    $settings['oauth2'],
    [
      new OAuth2\GrantType\RefreshToken($storage),
      new OAuth2\GrantType\UserCredentials($usersService),
    ]
  );
};

$container['oauth2-views'] = function (Container $container) {
  return new Slim\Views\PhpRenderer(base_path() . '/vendor/chadicus/slim-oauth2-routes/templates');
};

$container['serializer'] = function (Container $container) {
  return new \App\Util\Serializer();
};

$container['deserializer'] = function (Container $container) {
  return new \App\Util\Deserializer();
};

$container['filterer'] = function (Container $container) {
  return new \App\Util\Filterer();
};

$container['roles'] = function (Container $container) {
  return new \App\Services\RolesService($container);
};

$container['notifications'] = function (Container $container) {
  return new \App\Services\NotificationsService($container);
};

$container['scheduler'] = function (Container $container) {
  return new \App\Services\Scheduler($container);
};

$container['scheduler.worker'] = function (Container $container) {
  return new \App\Services\SchedulerWorker($container);
};

$container['super_closure.serializer'] = function (Container $container) {
  return new Serializer(
    new \SuperClosure\Analyzer\AstAnalyzer()
  );
};

$container['super_closure'] = function (Container $container) {
  return new Serializer(
    new \SuperClosure\Analyzer\AstAnalyzer()
  );
};

$container['reviews'] = function (Container $container) {
  return new \App\Services\ReviewsService($container);
};
