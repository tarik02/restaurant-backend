<?php

namespace App\Util;

abstract class OrderStatus {
  const UNKNOWN = -1;

  const WAITING = 0;
  const COOKING = 1;
  const INROAD = 2;
  const DONE = 3;
  
  public static function toString(int $value): string {
    switch ($value) {
      case self::WAITING:
        return 'waiting';

      case self::COOKING:
        return 'cooking';

      case self::INROAD:
        return 'inroad';

      case self::DONE:
        return 'done';
    }

    return 'unknown';
  }

  public static function fromString(string $value): int {
    switch ($value) {
      case 'waiting':
        return self::WAITING;

      case 'cooking':
        return self::COOKING;

      case 'inroad':
        return self::INROAD;

      case 'done':
        return self::DONE;
    }

    return self::UNKNOWN;
  }
}
