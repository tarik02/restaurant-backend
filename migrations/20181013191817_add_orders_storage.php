<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddOrdersStorage extends Migration {
  public function up() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->integer('storage_id')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('orders', function(Blueprint $table) {
      $table->dropColumn('storage_id');
    });
  }
}
