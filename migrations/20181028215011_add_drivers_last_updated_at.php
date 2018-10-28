<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddDriversLastUpdatedAt extends Migration {
  public function up() {
    Capsule::schema()->table('drivers', function(Blueprint $table) {
      $table->timestamp('last_updated_at')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('drivers', function(Blueprint $table) {
      $table->dropColumn('last_updated_at');
    });
  }
}
