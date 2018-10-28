<?php

namespace App\Util;

class Clock {
  private static $fake = null;

  public static function current(): \DateTime {
    if (self::$fake !== null) {
      return clone self::$fake;
    }

    return new \DateTime();
  }

  public static function fake(\DateTime $fake) {
    self::$fake = $fake;
  }
}