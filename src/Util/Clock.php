<?php

namespace App\Util;

class Clock {
  private static $fake = null;

  public static function current(): \DateTimeInterface {
    if (self::$fake !== null) {
      return self::$fake;
    }

    return new \DateTime();
  }

  public static function fake(\DateTimeInterface $fake) {
    self::$fake = $fake;
  }
}