<?php

namespace App\Util;

use App\Exceptions\ResponseException;
use Illuminate\Database\Query\Builder;
use Slim\Http\Body;
use Slim\Http\Response;

class Orderer {
  private $allowedFields = [];

  private $orderBy = null;
  private $descending = null;

  public function __construct() {
  }

  public function allow(string $field, ?bool $descending = null): self {
    $this->allowedFields[$field] = $descending;

    return $this;
  }

  public function by(?string $orderBy): self {
    if ($orderBy !== null) {
      $this->orderBy = $orderBy;
    }

    return $this;
  }

  public function descending(?bool $descending = null): self {
    if ($descending !== null) {
      $this->descending = $descending;
    }

    return $this;
  }

  public function apply(Response $response, Builder $builder): array {
    if ($this->orderBy !== null) {
      if (!array_key_exists($this->orderBy, $this->allowedFields)) {
        throw new ResponseException($response->withStatus(400, sprintf(
          'Ordering by "%s" is not allowed',
          $this->orderBy
        )));
      }

      $rule = $this->allowedFields[$this->orderBy];
      if ($rule !== null) {
        if ($this->descending === null) {
          $descending = $rule;
        } else {
          $descending = $this->descending;

          if ($descending !== $rule) {
            throw new ResponseException($response->withStatus(400, sprintf(
              'Ordering by "%s" in order "%s" is not allowed',
              $this->orderBy,
              $descending ? 'descending' : 'ascending'
            )));
          }
        }
      } else {
        $descending = $this->descending ?? false;
      }

      $builder->orderBy($this->orderBy, $descending ? 'desc' : 'asc');

      return [
        'by' => $this->orderBy,
        'direction' => $descending ? 'desc' : 'asc',
      ];
    }

    return null;
  }
}
