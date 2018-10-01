<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateNotifications extends Migration {
  public function up() {
    Capsule::schema()->create('notifications', function(Blueprint $table) {
      $table->increments('id');
      $table->integer('user_id');
      $table->json('data');
      $table->timestamp('created_at');
    });
  }

  public function down() {
    Capsule::schema()->drop('notifications');
  }
}
