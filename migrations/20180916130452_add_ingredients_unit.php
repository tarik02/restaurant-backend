<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddIngredientsUnit extends Migration {
  public function up() {
    Capsule::schema()->table('ingredients', function(Blueprint $table) {
      $table->string('unit')->nullable();
      $table->boolean('floating')->default(false);
    });
  }

  public function down() {
    Capsule::schema()->table('ingredients', function(Blueprint $table) {
      $table->dropColumn('unit');
      $table->dropColumn('floating');
    });
  }
}
