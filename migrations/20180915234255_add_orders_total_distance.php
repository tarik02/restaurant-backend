<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddOrdersTotalDistance extends Migration {
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->float('total_distance')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('total_distance');
    });
  }
}
