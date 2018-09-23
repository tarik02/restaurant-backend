<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateUsers extends Migration {
  public function up() {
    Capsule::schema()->create('users', function(Blueprint $table) {
      $table->increments('id');
      $table->string('username');
      $table->string('email');
      $table->string('password');
      $table->string('role');
    });
  }

  public function down() {
    Capsule::schema()->drop('users');
  }
}
