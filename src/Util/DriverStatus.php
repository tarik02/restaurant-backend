<?php

namespace App\Util;

abstract class DriverStatus {
  const UNKNOWN = -1;

  const READY = 0;
  const DRIVING = 1;

  public static function toString(int $value): string {
    switch ($value) {
      case self::READY:
        return 'ready';

      case self::DRIVING:
        return 'driving';

      case self::UNKNOWN:
      default:
        return 'unknown';
    }
  }

  public static function fromString(string $value): int {
    switch ($value) {
      case 'ready':
        return self::READY;

      case 'driving':
        return self::DRIVING;

      case 'unknown':
      default:
        return self::UNKNOWN;
    }
  }
}
