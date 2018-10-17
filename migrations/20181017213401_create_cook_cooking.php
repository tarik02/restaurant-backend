<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateCookCooking extends Migration {
  public function up() {
    Capsule::schema()->create('cook_cooking', function(Blueprint $table) {
      $table->increments('id');
      $table->integer('cook_id');
      $table->integer('order_id');
      $table->integer('course_id');
      $table->timestamp('created_at');
    });
  }

  public function down() {
    Capsule::schema()->drop('cook_cooking');
  }
}
