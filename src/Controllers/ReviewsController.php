<?php

namespace App\Controllers;

use App\Services\ResourcesService;
use App\Util\Filterer;
use App\Util\Orderer;
use App\Util\Paginator;
use App\Util\Serializer;
use Illuminate\Database\Connection;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class ReviewsController extends Controller {
  /** @var Connection */
  private $db;

  /** @var Filterer */
  private $filterer;

  /** @var Serializer */
  private $serializer;

  /** @var ResourcesService */
  private $resources;

  public function __construct(Container $container) {
    parent::__construct($container);

    $this->db = $container['db']->connection();
    $this->filterer = $container['filterer'];
    $this->serializer = $container['serializer'];
    $this->resources = $container['resources'];
  }

  public function get(Request $request, Response $response, array $args) {
    $this->assertAbility($request, $response, 'reviews');

    $query = $this->db->table('reviews');
    $query = $this->filterer->filter($query, $request->getParam('filter', []));

    $order = (new Orderer())
      ->allow('rating')
      ->allow('created_at')
      ->by('created_at')
      ->descending(true)
      ->by($request->getParam('sortBy'))
      ->descending($request->getParam('descending', 'false') === 'true')
      ->apply($response, $query);

    $pagination = (new Paginator())
      ->minPerPage(5)
      ->maxPerPage(100)
      ->page(intval($request->getParam('page', 1)))
      ->perPage(intval($request->getParam('perPage', 15)))
      ->apply($response, $query);

    $total = $query->getCountForPagination();
    $reviews = $query->get();

    return $response->withJson([
      'data' => $reviews->map(function (array $review) {
        return [
          'id' => intval($review['id']),

          'source' => $this->resources->getOptional($review['source_type'], $review['source_id']),
          'target' => $this->resources->getOptional($review['target_type'], $review['target_id']),

          'user' => $this->resources->getOptional('user', $review['user_id']),

          'text' => $review['text'],
          'rating' => $review['rating'] ? intval($review['rating']) : null,

          'created_at' => $this->serializer->dateTime($review['created_at']),
        ];
      }),

      'meta' => [
        'totalCount' => $total,

        'order' => $order,
        'pagination' => $pagination,
      ],
    ]);
  }
}
