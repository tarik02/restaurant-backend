<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class FunctionGeoDistance extends Migration {
  public function up() {
    Capsule::connection()->unprepared(
      <<<SQL
CREATE FUNCTION `geoDistance`(p1lat DOUBLE, p1lng DOUBLE, p2lat DOUBLE, p2lng DOUBLE) RETURNS DOUBLE
BEGIN
  SET @R = 6378137; -- Earth’s mean radius in meter
  
  SET @dLat = RADIANS(p2lat - p1lat);
  SET @dLng = RADIANS(p2lng - p1lng);
  
  SET @a =
    POW(SIN(@dLat / 2), 2) +
    POW(SIN(@dLng / 2), 2) *
    COS(RADIANS(p1lat)) * COS(RADIANS(p2lat));
  
  SET @c = 2 * ATAN2(SQRT(@a), SQRT(1 - @a));
  RETURN @R * @c; -- returns the distance in meter
END;
SQL
    );
  }

  public function down() {
    Capsule::connection()->unprepared(
      <<<SQL
DROP FUNCTION `geoDistance`;
SQL
    );
  }
}
