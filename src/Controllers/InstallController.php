<?php

namespace App\Controllers;

use App\Services\UsersService;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Monolog\Logger;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

class InstallController extends Controller {
  /** @var array */
  private $config;

  /** @var bool */
  private $installed;

  /** @var Container */
  private $container;

  /** @var Logger */
  private $logger;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->config = $container['settings'];
    $this->installed = $this->config['installed'];
    $this->container = $container;
    $this->logger = $container['logger'];
  }

  public function install(Request $request, Response $response, array $args) {
    global $app;

    if ($this->installed) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'installed',
      ]);
    }

    $env = [];
    $data = $request->getParsedBody();
    ['site' => $site, 'db' => $db, 'operator' => $operator] = $data;

    $env['INSTALLED'] = true;

    $env['APP_NAME'] = $site['title'];
    $env['APP_ENV'] = 'local';
    $env['APP_URL'] = $this->fixUrl($site['address']);

    $env['DB_CONNECTION'] = $db['connection'];
    $connection = $db[$db['connection']] ?? [];
    switch ($db['connection']) {
      case 'mysql':
        $env['DB_HOST'] = $connection['host'];
        $env['DB_PORT'] = intval($connection['port']);
        $env['DB_DATABASE'] = $connection['database'];
        $env['DB_USERNAME'] = $connection['user'];
        $env['DB_PASSWORD'] = $connection['password'];
        break;
      default:
        return $response->withJson([
          'status' => 'error',
          'reason' => 'wrong-db-connection',
        ]);
    }

    $fileEnv = base_path() . '/.env';
    $fileEnvTmp = base_path() . '/.env.tmp';
    @rename($fileEnv, $fileEnvTmp);
    $file = fopen($fileEnv, 'w');
    foreach ($env as $key => $value) {
      if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
      } else {
        $value = "$value";
      }

      if (false !== strpos($value, ' ')) {
        $value = "\"$value\"";
      }

      fputs($file, "$key=$value" . PHP_EOL);
    }
    fclose($file);

    $dotenv = new Dotenv(base_path());
    $dotenv->overload();
    unset($dotenv);

    // Reload config
    $app->setConfig(
      $this->config = require config_path() . '/app.php'
    );

    try {
      /** @var Connection $conn */
      $conn = $this->container['db']->getConnection();
    } catch (\Throwable $e) {
      @rename($fileEnvTmp, $fileEnv);
      $this->logger->error($e);

      return $response->withJson([
        'status' => 'error',
        'reason' => 'wrong-db-credentials',
      ]);
    }

    try {
      $conn->beginTransaction();
      {
        $command = new \Phpmig\Console\Command\MigrateCommand();
        $command->run(
          new ArgvInput([]),
          new NullOutput()
        );
      }

      /** @var UsersService $users */
      $users = $this->container['users'];

      $id = $users->register([
        'username' => $operator['username'],
        'email' => $operator['email'],
        'phone' => $operator['phone'],
        'password' => $operator['password'],
        'roles' => ['user', 'operator', 'reviews', 'storage', 'stats'],
      ]);

      if ($id === null) {
        @rename($fileEnvTmp, $fileEnv);
        $conn->rollBack();

        return $response->withJson([
          'status' => 'error',
          'reason' => 'user-exists',
        ]);
      }

      if ($db['autofill']) {
        $pdo = $conn->getPdo();

        $sql = file_get_contents(resources_path() . '/default.sql');
        $sql = trim($sql);
        if ($sql !== '') {
          $pdo->exec($sql);
        }
      }

      @unlink($fileEnvTmp);
      $conn->commit();
      return $response->withJson([
        'status' => 'ok',
      ]);
    } catch (\Throwable $e) {
      @rename($fileEnvTmp, $fileEnv);
      $this->logger->error($e);
      $conn->rollBack();

      return $response->withJson([
        'status' => 'error',
        'reason' => 'exception',
      ]);
    }
  }

  public function fixUrl(string $url): string {
    $parsed = parse_url($url);

    $protocol = $parsed['scheme'] ?? 'https';
    $host = explode('/', $parsed['host'] ?? $parsed['path'], 2)[0];

    return "$protocol://$host";
  }
}
