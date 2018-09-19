<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddOrdersCreatedAt extends Migration {
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->timestamp('created_at')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('created_at');
    });
  }
}
