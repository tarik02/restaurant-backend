<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddUsersPhone extends Migration {
  public function up() {
    Capsule::schema()->table('users', function(Blueprint $table) {
      $table->string('phone')->nullable();
    });
  }

  public function down() {
    Capsule::schema()->table('users', function(Blueprint $table) {
      $table->dropColumn('phone');
    });
  }
}
