<?php

namespace App\Resources;

interface ResourceProvider {
  public function get(int $id): array;
}
