<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class OrderCreatedAt extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->timestamp('created_at')->nullable();
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('created_at');
    });
  }
}
