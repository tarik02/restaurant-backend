<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class SetUsersEmailNullable extends Migration {
  public function up() {
    Capsule::schema()->table('users', function(Blueprint $table) {
      $table->string('email')->nullable()->change();
    });
  }

  public function down() {
    Capsule::schema()->table('users', function(Blueprint $table) {
      $table->string('email')->nullable(false)->change();
    });
  }
}
