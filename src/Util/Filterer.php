<?php

namespace App\Util;

use Illuminate\Database\Query\Builder;

class Filterer {
  public function filter(Builder $query, array $filter): Builder {
    return $query->whereNested(function (Builder $query) use ($filter) {
      foreach ($filter as $key => $values) {
        $query->whereIn($key, $values);
      }
    });
  }
}
