<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddOrdersToken extends Migration {
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->string('token')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('token');
    });
  }
}
