<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class DropDrivers extends Migration {
  public function up() {
    Capsule::schema()->drop('drivers');
  }

  public function down() {
    Capsule::schema()->create('drivers', function(Blueprint $table) {
      $table->increments('id');

      $table->string('name');

      $table->float('lat');
      $table->float('lng');
    });
  }
}
