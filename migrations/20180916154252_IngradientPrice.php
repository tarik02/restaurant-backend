<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class IngradientPrice extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('ingradients', function(Blueprint $table) {
      $table->integer('price')->default(0);
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('ingradients', function(Blueprint $table) {
      $table->dropColumn('price');
    });
  }
}
