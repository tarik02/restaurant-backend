<?php

namespace App\Controllers;

use App\Services\ResourcesService;
use Illuminate\Database\Connection;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class CookController extends Controller {
  /** @var Connection */
  private $db;

  /** @var ResourcesService */
  private $resources;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->db = $container['db']->connection();
    $this->resources = $container['resources'];
  }

  public function dashboard(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'cook');

    // TODO:

    return $response->withJson([
      'status' => 'ok',
    ]);
  }
}
