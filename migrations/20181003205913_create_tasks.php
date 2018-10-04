<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateTasks extends Migration {
  public function up() {
    Capsule::schema()->create('tasks', function(Blueprint $table) {
      $table->increments('id');
      $table->integer('status');
      $table->timestamp('next_run');
      $table->text('data');
    });
  }

  public function down() {
    Capsule::schema()->drop('tasks');
  }
}
