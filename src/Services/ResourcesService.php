<?php

namespace App\Services;

use App\Resources\ResourceProvider;
use Slim\Container;

class ResourcesService {
  /** @var Container */
  private $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

  public function getResourceProvider(string $type): ResourceProvider {
    return $this->container["resource-{$type}"];
  }

  public function get(string $type, int $id): array {
    if (($resource = $this->getOptional($type, $id)) !== null) {
      return $resource;
    }

    throw new \RuntimeException('Resource does not exist');
  }

  public function getOptional(?string $type, ?int $id): ?array {
    if ($type === null || $id === null) {
      return null;
    }

    $provider = $this->getResourceProvider($type);

    if (($data = $provider->get($id)) !== null) {
      return [
        'type' => $type,
        'data' => $data,
      ];
    }

    return null;
  }
}
