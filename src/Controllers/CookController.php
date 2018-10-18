<?php

namespace App\Controllers;

use App\Services\ResourcesService;
use App\Util\Deserializer;
use App\Util\OrderStatus;
use App\Util\Serializer;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class CookController extends Controller {
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

  public function getCook(int $id): array {
    return $this->resources->get('cook', $id);
  }

  public function dashboard(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'cook');
    $user = $this->getUser($request);
    $cook = $this->getCook($user['id']);

    $cooking = $this->db->table('cook_cooking')
      ->where('cook_id', $user['id'])
      ->orderBy('created_at')
      ->get();

    $cookQueue = $this->db->table('orders_courses')
      ->where('remaining', '<>', 0)
      ->leftJoin('orders', 'orders_courses.order_id', '=', 'orders.id')
      ->where('orders.status', OrderStatus::WAITING)
      ->where('orders.storage_id', $cook['storage_id'])
      ->orderBy('orders.created_at')
      ->orderBy('orders_courses.order_id')
      ->orderBy('orders_courses.course_id')
      ->get([
        'orders.id as order_id',
        'orders_courses.course_id',
        'orders_courses.count',
        'orders_courses.remaining',
        'orders_courses.done',
      ]);

    return $response->withJson([
      'status' => 'ok',
      'cookingQueue' => $cooking->map(function (array $course) {
        return [
          'id' => intval($course['id']),
          'order_id' => intval($course['order_id']),
          'course_id' => intval($course['course_id']),
          'created_id' => $this->deserializer->dateTime($course['created_at'])->format('Y-m-d H:i:s'),
        ];
      }),
      'cookQueue' => $cookQueue->map(function (array $course) {
        return [
          'order_id' => intval($course['order_id']),
          'course_id' => intval($course['course_id']),
          'count' => intval($course['count']),
          'remaining' => intval($course['remaining']),
          'done' => intval($course['done']),
        ];
      }),
    ]);
  }

  public function startCooking(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'cook');
    $user = $this->getUser($request);
    $cook = $this->getCook($user['id']);

    $orderId = intval($args['order_id']);
    $courseId = intval($args['course_id']);
    $count = $request->getParam('count', 1);

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->db->beginTransaction();

    try {
      $remaining = intval(
        $this->db->table('orders_courses')
          ->where('order_id', $orderId)
          ->where('course_id', $courseId)
          ->first(['remaining'])['remaining']
      );

      if ($remaining < $count) {
        $this->db->rollBack();
        return $response->withJson([
          'status' => 'error',
          'reason' => 'out-of',
        ]);
      }

      $this->db->table('orders_courses')
        ->where('order_id', $orderId)
        ->where('course_id', $courseId)
        ->update([
          'remaining' => $remaining - $count,
        ]);

      $this->db->table('cook_cooking')->insert(
        array_map(function () use ($cook, $orderId, $courseId) {
          return [
            'cook_id' => $cook['id'],
            'order_id' => $orderId,
            'course_id' => $courseId,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
          ];
        }, range(1, $count))
      );

      $this->db->commit();
      return $response->withJson([
        'status' => 'ok',
      ]);
    } catch (\Throwable $e) {
      /** @noinspection PhpUnhandledExceptionInspection */
      $this->db->rollBack();
      /** @noinspection PhpUnhandledExceptionInspection */
      throw $e;
    }
  }

  public function cancelCooking(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'cook');
    $user = $this->getUser($request);
    $cook = $this->getCook($user['id']);

    $id = intval($args['id']);

    $this->db->beginTransaction();

    try {
      $row = $this->db->table('cook_cooking')
        ->where('id', $id)
        ->where('cook_id', $cook['id'])
        ->first(['order_id', 'course_id']);
      if ($row === null) {
        $this->db->rollBack();
        return $response->withJson([
          'status' => 'error',
          'reason' => 'not-exists',
        ]);
      }
      [
        'order_id' => $orderId,
        'course_id' => $courseId,
      ] = $row;

      $this->db->table('cook_cooking')
        ->where('id', $id)
        ->where('cook_id', $cook['id'])
        ->delete();

      $remaining = intval(
        $this->db->table('orders_courses')
          ->where('order_id', $orderId)
          ->where('course_id', $courseId)
          ->first(['remaining'])['remaining']
      );

      $this->db->table('orders_courses')
        ->where('order_id', $orderId)
        ->where('course_id', $courseId)
        ->update([
          'remaining' => $remaining + 1,
        ]);

      $this->db->commit();
      return $response->withJson([
        'status' => 'ok',
      ]);
    } catch (\Throwable $e) {
      /** @noinspection PhpUnhandledExceptionInspection */
      $this->db->rollBack();
      /** @noinspection PhpUnhandledExceptionInspection */
      throw $e;
    }
  }

  public function doneCooking(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'cook');
    $user = $this->getUser($request);
    $cook = $this->getCook($user['id']);

    $id = intval($args['id']);

    $this->db->beginTransaction();

    try {
      $row = $this->db->table('cook_cooking')
        ->where('id', $id)
        ->where('cook_id', $cook['id'])
        ->first(['order_id', 'course_id']);
      if ($row === null) {
        $this->db->rollBack();
        return $response->withJson([
          'status' => 'error',
          'reason' => 'not-exists',
        ]);
      }
      [
        'order_id' => $orderId,
        'course_id' => $courseId,
      ] = $row;

      $this->db->table('cook_cooking')
        ->where('id', $id)
        ->where('cook_id', $cook['id'])
        ->delete();

      $row = $this->db->table('orders_courses')
        ->where('order_id', $orderId)
        ->where('course_id', $courseId)
        ->first(['count', 'done']);
      $count = intval($row['count']);
      $done = intval($row['done']);

      $this->db->table('orders_courses')
        ->where('order_id', $orderId)
        ->where('course_id', $courseId)
        ->update([
          'done' => $done + 1,
        ]);

      $ordersCourses = $this->db->table('orders_courses')
        ->where('order_id', $orderId)
        ->get(['count', 'done'])
      ;

      $doneOrder = true;
      foreach ($ordersCourses as $course) {
        if ($course['count'] !== $course['done']) {
          $doneOrder = false;
          break;
        }
      }
      if ($doneOrder) {
        $this->db->table('orders')
          ->where('id', $orderId)
          ->update([
            'status' => OrderStatus::WAITING_FOR_DRIVER,
          ]);
      }

      $this->db->commit();
      return $response->withJson([
        'status' => 'ok',
      ]);
    } catch (\Throwable $e) {
      /** @noinspection PhpUnhandledExceptionInspection */
      $this->db->rollBack();
      /** @noinspection PhpUnhandledExceptionInspection */
      throw $e;
    }
  }
}
