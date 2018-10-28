<?php

namespace App\Services;

// https://maps.googleapis.com/maps/api/directions/json?origin=Disneyland&destination=Universal+Studios+Hollywood&key=AIzaSyBLs45gvYkgg_zzBnQMIot2yTztybP1GL4

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Slim\Container;
use GuzzleHttp;

class MapsService {
  /** @var string */
  private $token;

  private $client;

  public function __construct(Container $container) {
    $settings = $container['settings'];
    $this->token = $settings['googleMapsToken'];

    $handler = new CurlHandler();
    $stack = HandlerStack::create($handler);
    $this->client = new GuzzleHttp\Client([
      'base_uri' => 'https://maps.googleapis.com/maps/api/',
      'timeout' => 2.0,

      'handler' => $stack,
    ]);

    $stack->unshift(Middleware::mapRequest(function (RequestInterface $request) {
      return $request->withUri(Uri::withQueryValue($request->getUri(), 'key', $this->token));
    }));
  }

  public function estimatedTravelTime($a, $b) {
    $response = $this->client->get('directions/json', [
      'query' => [
        'origin' => $a['lat'] . ',' . $a['lng'],
        'destination' => $b['lat'] . ',' . $b['lng'],
      ],
    ]);

    $contents = json_decode($response->getBody()->getContents(), true);
    if ($contents['status'] !== 'OK') {
      return INF;
    }

    $routes = $contents['routes'];
    $route = $routes[0] ?? null;

    if ($route === null) {
      return INF;
    }

    $legs = $route['legs'];
    $leg = $legs[0] ?? null;

    if ($leg === null) {
      return INF;
    }

    $duration = $leg['duration'];

    return $duration['value'];
  }
}