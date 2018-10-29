<?php

namespace App\Controllers;

use App\Services\ResourcesService;
use App\Util\Deserializer;
use App\Util\OrderStatus;
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

    $dayOfWeek = $request->getParam('dayOfWeek') === 'true';
    $since = $this->deserializer->dateTime($this->assert($response, $request->getParam('since')));
    $until = $this->deserializer->dateTime($this->assert($response, $request->getParam('until')));

    $stats = collect($this->db->select(str_replace(
      'DATEFUNCTION',
      $dayOfWeek ? 'DAYOFWEEK' : 'DATE',
<<<SQL
    SELECT
      orders_courses.course_id,
      DATEFUNCTION(orders.created_at) as day,
      SUM(orders_courses.count) as count
    FROM orders_courses
    LEFT JOIN orders ON orders.id = orders_courses.order_id
    WHERE
      orders.status = :orderStatus AND
      DATE(orders.created_at) BETWEEN :since AND :until
    GROUP BY day, orders_courses.course_id
SQL
    ), [
      'orderStatus' => OrderStatus::DONE,
      'since' => $since->format('Y-m-d'),
      'until' => $until->format('Y-m-d'),
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

  public function income(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'stats');

    $dayOfWeek = $request->getParam('dayOfWeek') === 'true';
    $since = $this->deserializer->dateTime($this->assert($response, $request->getParam('since')));
    $until = $this->deserializer->dateTime($this->assert($response, $request->getParam('until')));

    $stats = collect($this->db->select(str_replace(
      'DATEFUNCTION',
      $dayOfWeek ? 'DAYOFWEEK' : 'DATE',
<<<SQL
    SELECT
      DATEFUNCTION(orders.created_at) as day,
      SUM(orders.price) as price
    FROM orders
    WHERE
      orders.status = :orderStatus AND
      DATE(orders.created_at) BETWEEN :since AND :until
    GROUP BY day
SQL
      ), [
        'orderStatus' => OrderStatus::DONE,
        'since' => $since->format('Y-m-d'),
        'until' => $until->format('Y-m-d'),
      ]))
      ->mapWithKeys(function (array $data) {
        return [$data['day'] => intval($data['price'])];
      })
    ;

    return $response->withJson($stats);
  }

  public function ingredients(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'stats');

    $dayOfWeek = $request->getParam('dayOfWeek') === 'true';
    $since = $this->deserializer->dateTime($this->assert($response, $request->getParam('since')));
    $until = $this->deserializer->dateTime($this->assert($response, $request->getParam('until')));

    $stats = collect($this->db->select(str_replace(
      'DATEFUNCTION',
      $dayOfWeek ? 'DAYOFWEEK' : 'DATE',
<<<SQL
    SELECT
      DATEFUNCTION(orders.created_at) as day,
      ingredients.title as ingredient,
      ingredients.unit as unit,
      ROUND(SUM(courses_ingredients.amount * orders_courses.count), 1) as count
    FROM orders
    LEFT JOIN orders_courses ON orders_courses.order_id = orders.id
    LEFT JOIN courses_ingredients ON courses_ingredients.course_id = orders_courses.course_id
    LEFT JOIN ingredients ON ingredients.id = courses_ingredients.ingredient_id
    WHERE
      orders.status = :orderStatus AND
      DATE(orders.created_at) BETWEEN :since AND :until
    GROUP BY day, courses_ingredients.ingredient_id
SQL
      ), [
        'orderStatus' => OrderStatus::DONE,
        'since' => $since->format('Y-m-d'),
        'until' => $until->format('Y-m-d'),
      ]))
      ->mapToGroups(function (array $data) {
        return [$data['ingredient'] => $data];
      })
      ->map(function ($data) {
        return collect($data)->mapWithKeys(function (array $data) {
          return [$data['day'] => [
            'count' => floatval($data['count']),
            'unit' => $data['unit'],
          ]];
        });
      })
    ;

    return $response->withJson($stats);
  }

  public function ingredientsOutdated(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'stats');

    $since = $this->deserializer->dateTime($this->assert($response, $request->getParam('since')));
    $until = $this->deserializer->dateTime($this->assert($response, $request->getParam('until')));

    $stats = collect($this->db->select(
<<<SQL
    SELECT
      DATE(storages_batches.best_by) as day,
      ingredients.title as ingredient,
      ingredients.unit as unit,
      ROUND(SUM(storages_batches.remaining), 1) as count
    FROM storages_batches_old
    LEFT JOIN storages_batches ON storages_batches.id = storages_batches_old.id
    LEFT JOIN ingredients ON ingredients.id = storages_batches.ingredient_id
    WHERE
      DATE(storages_batches.best_by) BETWEEN :since AND :until
    GROUP BY day, ingredients.id
SQL
    , [
      'since' => $since->format('Y-m-d'),
      'until' => $until->format('Y-m-d'),
    ]))
      ->mapToGroups(function (array $data) {
        return [$data['ingredient'] => $data];
      })
      ->map(function ($data) {
        return collect($data)->mapWithKeys(function (array $data) {
          return [$data['day'] => [
            'count' => floatval($data['count']),
            'unit' => $data['unit'],
          ]];
        });
      })
    ;

    return $response->withJson($stats);
  }

  public function orders(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'stats');

    $dayOfWeek = $request->getParam('dayOfWeek') === 'true';
    $since = $this->deserializer->dateTime($this->assert($response, $request->getParam('since')));
    $until = $this->deserializer->dateTime($this->assert($response, $request->getParam('until')));

    $stats = collect($this->db->select(str_replace(
      'DATEFUNCTION',
      $dayOfWeek ? 'DAYOFWEEK' : 'DATE',
      <<<SQL
    SELECT
      DATEFUNCTION(orders.created_at) as day,
      COUNT(orders.id) as count
    FROM orders
    WHERE
      orders.status = :orderStatus AND
      DATE(orders.created_at) BETWEEN :since AND :until
    GROUP BY day
SQL
    ), [
      'orderStatus' => OrderStatus::DONE,
      'since' => $since->format('Y-m-d'),
      'until' => $until->format('Y-m-d'),
    ]))
      ->mapWithKeys(function (array $data) {
        return [$data['day'] => intval($data['count'])];
      })
    ;

    return $response->withJson($stats);
  }
}