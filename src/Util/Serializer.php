<?php

namespace App\Util;

class Serializer {
  const DATETIME_FORMAT = \DateTime::ISO8601;

  public function dateTime($dateTime): string {
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
