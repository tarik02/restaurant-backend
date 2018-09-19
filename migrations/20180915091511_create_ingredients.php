<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateIngredients extends Migration {
  public function up() {
    Capsule::schema()->create('ingredients', function(Blueprint $table) {
      $table->increments('id');
      $table->string('title');
    });
  }

  public function down() {
    Capsule::schema()->drop('ingredients');
  }
}
