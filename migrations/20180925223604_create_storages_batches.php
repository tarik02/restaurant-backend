<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateStoragesBatches extends Migration {
  public function up() {
    Capsule::schema()->create('storages_batches', function(Blueprint $table) {
      $table->increments('id');

      $table->integer('storage_id');
      $table->integer('ingredient_id');

      $table->float('count');
      $table->float('remaining');
      $table->timestamp('best_by');
    });
  }

  public function down() {
    Capsule::schema()->drop('storages_batches');
  }
}
