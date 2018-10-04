<?php

namespace App\Util;

use Illuminate\Database\Query\Builder;
use Slim\Http\Response;

class Paginator {
  private $page = 1;
  private $perPage = 0;
  private $minPerPage = 1;
  private $maxPerPage = 10;

  public function __construct() {
  }

  public function page(int $page): self {
    $this->page = $page;

    return $this;
  }

  public function perPage(int $perPage): self {
    $this->perPage = $perPage;

    return $this;
  }

  public function minPerPage(int $min): self {
    $this->minPerPage = $min;

    return $this;
  }

  public function maxPerPage(int $max): self {
    $this->maxPerPage = $max;

    return $this;
  }

  public function apply(Response $response, Builder $builder): array {
    $builder->forPage(
      max($this->page, 1),
      clamp($this->perPage, $this->minPerPage, $this->maxPerPage)
    );

    return [
      'page' => $this->page,
      'perPage' => $this->perPage,
    ];
  }
}
