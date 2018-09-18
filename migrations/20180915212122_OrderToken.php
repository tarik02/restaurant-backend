<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class OrderToken extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->string('token')->nullable();
    });
  }

  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('token');
    });
  }
}
