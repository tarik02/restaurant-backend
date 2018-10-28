<?php

namespace App\Util;

abstract class DriverStatus {
  const UNKNOWN = -1;

  const OFF = 0;
  const DRIVING = 1;
  const IDLE = 2;

  public static function toString(int $value): string {
    switch ($value) {
      case self::OFF:
        return 'off';

      case self::DRIVING:
        return 'driving';

      case self::IDLE:
        return 'idle';

      case self::UNKNOWN:
      default:
        return 'unknown';
    }
  }

  public static function fromString(string $value): int {
    switch ($value) {
      case 'off':
        return self::OFF;

      case 'driving':
        return self::DRIVING;

      case 'idle':
        return self::IDLE;

      case 'unknown':
      default:
        return self::UNKNOWN;
    }
  }
}
