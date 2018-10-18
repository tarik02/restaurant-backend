<?php

namespace App\Controllers;

use App\Services\ResourcesService;
use App\Util\Deserializer;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class StatsController extends Controller {
  /** @var Connection */
  private $db;

  /** @var ResourcesService */
  private $resources;

  /** @var Deserializer */
  private $deserializer;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->db = $container['db']->connection();
    $this->resources = $container['resources'];
    $this->deserializer = $container['deserializer'];
  }

  public function courses(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'stats');

    $since = (new \DateTime())->sub(new \DateInterval('P5D'));
    $until = new \DateTime();

    $stats = collect($this->db->select(<<<SQL
    SELECT
      orders_courses.course_id,
      DATE(orders.created_at) as day,
      SUM(orders_courses.count) as count
    FROM orders_courses
    LEFT JOIN orders ON orders.id = orders_courses.order_id
    WHERE
      orders.created_at BETWEEN :since AND :until
    GROUP BY day, orders_courses.course_id
SQL
    , [
      'since' => $since,
      'until' => $until,
    ]))
      ->mapToGroups(function (array $data) {
        return [$data['course_id'] => $data];
      })
      ->map(function ($data) {
        return collect($data)->mapWithKeys(function (array $data) {
          return [$data['day'] => intval($data['count'])];
        });
      })
    ;

    return $response->withJson($stats);
  }
}