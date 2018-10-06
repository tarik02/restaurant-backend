<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateDrivers2 extends Migration {
  public function up() {
    Capsule::schema()->create('drivers', function(Blueprint $table) {
      $table->integer('driver_id')->unsigned();

      $table->float('latitude')->nullable();
      $table->float('longitude')->nullable();

      $table->primary(['driver_id']);
    });
  }

  public function down() {
    Capsule::schema()->drop('drivers');
  }
}
