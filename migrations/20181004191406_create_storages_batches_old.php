<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateStoragesBatchesOld extends Migration {
  public function up() {
    Capsule::schema()->create('storages_batches_old', function(Blueprint $table) {
      $table->increments('id');
    });
  }

  public function down() {
    Capsule::schema()->drop('storages_batches_old');
  }
}
