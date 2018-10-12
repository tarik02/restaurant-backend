<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddStoragesAddress extends Migration {
  public function up() {
    Capsule::schema()->table('storages', function(Blueprint $table) {
      $table->string('address')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('storages', function(Blueprint $table) {
      $table->dropColumn('address');
    });
  }
}
