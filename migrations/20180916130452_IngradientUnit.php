<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class IngradientUnit extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('ingradients', function(Blueprint $table) {
      $table->string('unit')->nullable();
      $table->boolean('floating')->default(false);
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('ingradients', function(Blueprint $table) {
      $table->dropColumn('unit');
      $table->dropColumn('floating');
    });
  }
}
