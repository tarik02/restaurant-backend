<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateCooks extends Migration {
  public function up() {
    Capsule::schema()->create('cooks', function(Blueprint $table) {
      $table->integer('user_id');
      $table->integer('storage_id');
    });
  }

  public function down() {
    Capsule::schema()->drop('cooks');
  }
}
