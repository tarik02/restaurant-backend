<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class OrderStatus extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->integer('status')->default(0);
      $table->integer('driver_id')->nullable();
    });
  }

  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('status');
      $table->dropColumn('driver_id');
    });
  }
}
