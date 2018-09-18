<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class OrderTotalDistance extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->float('totalDistance')->nullable();
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('totalDistance');
    });
  }
}
