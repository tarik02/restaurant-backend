<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddUsersRoles extends Migration {
  public function up() {
    Capsule::schema()->table('users', function(Blueprint $table) {
      $table->json('roles')->default('["user"]');
    });
  }

  public function down() {
    Capsule::schema()->table('users', function(Blueprint $table) {
      $table->dropColumn('roles');
    });
  }
}
