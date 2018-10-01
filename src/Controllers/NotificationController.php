<?php

namespace App\Controllers;

use App\Services\NotificationsService;
use App\Util\Serializer;
use Illuminate\Database\Capsule\Manager as DB;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class NotificationController extends Controller {
  /** @var NotificationsService */
  private $notifications;

  /** @var Serializer */
  private $serializer;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->notifications = $container['notifications'];
    $this->serializer = $container['serializer'];
  }

  public function getAndFlush(Request $request, Response $response, array $args) {
    $user = $this->assertUser($request, $response);

    $notifications = $this->notifications->flush(intval($user['id']));

    return $response->withJson(
      $notifications->map(function (array $notification) {
        return [
          'data' => json_decode($notification['data'], true),
          'created_at' => $this->serializer->dateTime($notification['created_at']),
        ];
      })
    );
  }
}
