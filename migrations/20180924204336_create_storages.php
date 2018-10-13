<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateStorages extends Migration {
  public function up() {
    Capsule::schema()->create('storages', function(Blueprint $table) {
      $table->increments('id');
      $table->string('name');

      $table->double('latitude')->nullable();
      $table->double('longitude')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->drop('storages');
  }
}
