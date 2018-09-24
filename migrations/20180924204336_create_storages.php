<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateStorages extends Migration {
  public function up() {
    Capsule::schema()->create('storages', function(Blueprint $table) {
      $table->increments('id');
      $table->string('name');

      $table->float('latitude')->nullable();
      $table->float('longitude')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->drop('storages');
  }
}
