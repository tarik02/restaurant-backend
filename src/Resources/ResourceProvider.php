<?php

namespace App\Resources;

abstract class ResourceProvider {
  public function derived(array $resource): array {
    return [];
  }

  abstract function get(int $id): array;

  abstract function fromDB(array $original): array;
}
