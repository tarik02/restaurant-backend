<?php

namespace App\Util;

abstract class OrderStatus {
  const UNKNOWN = -1;

  const WAITING = 0;
  const COOKING = 1;
  const WAITING_FOR_DRIVER = 2;
  const INROAD = 3;
  const DONE = 4;
  const CANCELLED = 5;
  
  public static function toString(int $value): string {
    switch ($value) {
      case self::WAITING:
        return 'waiting';

      case self::COOKING:
        return 'cooking';

      case self::WAITING_FOR_DRIVER:
        return 'waiting_for_driver';

      case self::INROAD:
        return 'inroad';

      case self::DONE:
        return 'done';

      case self::CANCELLED:
        return 'cancelled';
    }

    return 'unknown';
  }

  public static function fromString(string $value): int {
    switch ($value) {
      case 'waiting':
        return self::WAITING;

      case 'cooking':
        return self::COOKING;

      case 'waiting_for_driver':
        return self::WAITING_FOR_DRIVER;

      case 'inroad':
        return self::INROAD;

      case 'done':
        return self::DONE;

      case 'cancelled':
        return self::CANCELLED;
    }

    return self::UNKNOWN;
  }
}
