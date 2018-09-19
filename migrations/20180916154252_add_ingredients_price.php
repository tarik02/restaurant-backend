<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddIngredientsPrice extends Migration {
  public function up() {
    Capsule::schema()->table('ingredients', function(Blueprint $table) {
      $table->integer('price')->default(0);
    });
  }

  public function down() {
    Capsule::schema()->table('ingredients', function(Blueprint $table) {
      $table->dropColumn('price');
    });
  }
}
