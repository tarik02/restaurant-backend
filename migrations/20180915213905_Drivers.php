<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Drivers extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->create('drivers', function(Blueprint $table) {
      $table->increments('id');

      $table->string('name');

      $table->float('lat');
      $table->float('lng');
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->drop('drivers');
  }
}
