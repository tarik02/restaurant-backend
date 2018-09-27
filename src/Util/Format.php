<?php

namespace App\Util;

abstract class Format {
  const DATETIME_FORMAT = \DateTime::ISO8601;

  private function __construct() {
  }

  public static function dateTime($dateTime) {
    if (!($dateTime instanceof \DateTimeInterface)) {
      if (is_numeric($dateTime)) {
        $dateTime = new \DateTime("@{$dateTime}");
      } else {
        $dateTime = new \DateTime($dateTime);
      }
    }

    return $dateTime->format(self::DATETIME_FORMAT);
  }
}
