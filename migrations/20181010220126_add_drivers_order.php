<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddDriversOrder extends Migration {
  public function up() {
    Capsule::schema()->table('drivers', function(Blueprint $table) {
      $table->integer('order_id')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('drivers', function(Blueprint $table) {
      $table->dropColumn('order_id');
    });
  }
}
