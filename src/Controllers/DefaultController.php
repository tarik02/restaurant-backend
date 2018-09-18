<?php

namespace App\Controllers;

use App\Util\OrderStatus;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use Slim\Http\Request;
use Slim\Http\Response;

class DefaultController {
  public function courses(Request $request, Response $response, array $args) {
    $result = DB::table('courses')
      ->where('visible', 1)
      ->orderBy('id', 'desc')
      ->get();
    $coursesIds = $result->pluck('id');

    $images = DB::table('courses_images')
      ->whereIn('course_id', $coursesIds)
      ->get()
      ->groupBy('course_id');

    return $response->withJson([
      'data' => $result->map(function(array $course) use($images, $request) {
        $id = intval($course['id']);

        /** @var Collection|null $courseImages */
        $courseImages = $images[$id] ?? null;

        return [
          'id' => $id,
          'title' => $course['title'],
          'description' => $course['description'],
          'images' => $courseImages !== null
            ? collect($courseImages)->pluck('src')->toArray()
            : [],
          'price' => intval($course['price']),
        ];
      }),
    ]);
  }

  public function order(Request $request, Response $response, array $args) {
    $body = $request->getParsedBody();

    /** @noinspection PhpUnhandledExceptionInspection */
    return DB::connection()->transaction(function () use ($body, $request, $response) {
      $courses = DB::table('courses');
      $orders = DB::table('orders');
      $ordersCourses = DB::table('orders_courses');

      [
        'cart' => $cart,
        'info' => $info,
        'target' => $target,
      ] = $body;

      $cartItems = $courses->whereIn('id', array_keys($cart))->get();
      if (count($cartItems) !== count($cart)) {
        return $response->withJson([
          'status' => 'change_count',
        ]);
      }

      $price = 0;
      foreach ($cartItems as $item) {
        $count = $cart[$item['id']];
        $price += $count * $item['price'];
      }

//      TODO: compare price
//      if ($price !== $info['price']) {
//        return $request->withJson([
//          'status' => 'change_price',
//        ]);
//      }

      $id = $orders->insertGetId([
        'contact_name' => $info['name'],
        'phone' => $info['phone'],

        'created_at' => new \DateTime(),
        'price' => $price,

        'address' => $target['address'],
        'latitude' => $target['coordinates']['lat'],
        'longtitude' => $target['coordinates']['lng'],
        'notes' => $info['notes'],

        'token' => $token = str_random(64),
      ]);

      $ordersCourses->insert($cartItems->map(function ($item) use ($cart, $id) {
        return [
          'order_id' => $id,
          'course_id' => $item['id'],
          'count' => $cart[$item['id']],
        ];
      })->toArray());

      return $response->withJson([
        'status' => 'ok',
        'order_id' => $id,
        'token' => $token,
      ]);
    });
  }

  public function orderWatch(Request $request, Response $response, array $args) {
    ['id' => $id, 'token' => $token] = $args;

    $orders = DB::table('orders');
    $drivers = DB::table('drivers');

    $order = $orders->find($id);
    if ($order === null) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'existance',
      ]);
    }

    if ($token !== $order['token']) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'token',
      ]);
    }

    $result = [];
    if (intval($order['status']) === OrderStatus::INROAD) {
      $driver = $drivers->find($order['driver_id']);
      if (null === $driver) {
        $order['status'] = OrderStatus::UNKNOWN;
      } else {
        $result = [
          'driver' => [
            'id' => $driver['id'],
            'name' => $driver['name'],
            'lat' => floatval($driver['lat']),
            'lng' => floatval($driver['lng']),
          ],
          'target' => [
            'address' => $order['address'],
            'lat' => floatval($order['latitude']),
            'lng' => floatval($order['longtitude']),
          ],
          'totalDistance' => $order['totalDistance'],
        ];
      }
    }

    $result['status'] = OrderStatus::toString(intval($order['status']));

    return $response->withJson($result);
  }
}
