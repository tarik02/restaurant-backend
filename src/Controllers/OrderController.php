<?php

namespace App\Controllers;

use App\Services\ReviewsService;
use App\Util\OrderStatus;
use Illuminate\Database\Capsule\Manager as DB;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class OrderController extends Controller {
  /** @var ReviewsService */
  private $reviews;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->reviews = $container['reviews'];
  }

  protected function getOrder(Response $response, int $id, ?string $token = null): array {
    $orders = DB::table('orders');

    $order = $orders->find($id);
    if ($order === null) {
      $this->throwResponse($response->withJson([
        'status' => 'error',
        'reason' => 'not-found',
      ]));
    }

    if ($token !== null && $token !== $order['token']) {
      $this->throwResponse($response->withJson([
        'status' => 'error',
        'reason' => 'wrong-token',
      ]));
    }

    return $order;
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

      if ($price !== intval($info['price'])) {
        return $response->withJson([
          'status' => 'change_price',
        ]);
      }

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

  public function watch(Request $request, Response $response, array $args) {
    ['id' => $id, 'token' => $token] = $args;
    $order = $this->getOrder($response, $id, $token);

    $result = [
      'status' => OrderStatus::toString(intval($order['status'])),

      'needsReview' => $order['review_id'] === null,
    ];

    if (intval($order['status']) === OrderStatus::INROAD) {
      $driverId = intval($order['driver_id']);
      $user = DB::table('users')->where('id', $driverId)->first(['username']);
      $driver = DB::table('drivers')->where('driver_id', $driverId)->first();
      if (null === $driver) {
        $order['status'] = OrderStatus::UNKNOWN;
      } else {
        $result += [
          'driver' => [
            'id' => $driverId,
            'name' => $user['username'],
            'lat' => floatval($driver['latitude']),
            'lng' => floatval($driver['longitude']),
          ],
          'target' => [
            'address' => $order['address'],
            'lat' => floatval($order['latitude']),
            'lng' => floatval($order['longtitude']),
          ],
          'totalDistance' => $order['total_distance'],
        ];
      }
    }

    return $response->withJson($result);
  }

  public function rate(Request $request, Response $response, array $args) {
    $user = $this->getUser($request);

    ['id' => $id, 'token' => $token] = $args;
    $order = $this->getOrder($response, $id, $token);

    $text = $request->getParsedBodyParam('text');
    $rating = $request->getParsedBodyParam('rating');

    if ($order['review_id'] !== null) {
      return $response->withJson([
        'status' => 'error',
        'reason' => 'already',
      ]);
    }

    $id = $this->reviews->createReview([
      'source' => [
        'type' => 'order',
        'id' => $id,
      ],

      'target' => [
        'type' => 'driver',
        'id' => $order['driver_id'],
      ],

      'user_id' => $user ? $user['id'] : null,

      'text' => $text,
      'rating' => $rating,
    ]);

    DB::table('orders')->where('id', $order['id'])->update([
      'review_id' => $id,
    ]);

    return $response->withJson([
      'status' => 'ok',
      'id' => $id,
    ]);
  }

  public function dontRate(Request $request, Response $response, array $args) {
    ['id' => $id, 'token' => $token] = $args;
    $order = $this->getOrder($response, $id, $token);

    DB::table('orders')->where('id', $order['id'])->update([
      'review_id' => 0,
    ]);

    return $response->withJson([
      'status' => 'ok',
    ]);
  }
}
