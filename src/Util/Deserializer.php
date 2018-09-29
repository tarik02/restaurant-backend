<?php

namespace App\Util;

class Deserializer {
  const DATETIME_FORMAT = \DateTime::ISO8601;

  public function dateTime($dateTime): \DateTimeInterface {
    if ($dateTime instanceof \DateTimeInterface) {
      return $dateTime;
    }

    if (is_numeric($dateTime)) {
      return new \DateTime("@{$dateTime}");
    } else {
      return new \DateTime($dateTime);
    }
  }
}
