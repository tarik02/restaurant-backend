<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateCourses extends Migration {
  public function up() {
    Capsule::schema()->create('courses', function(Blueprint $table) {
      $table->increments('id');
      $table->string('title');
      $table->string('description');
      $table->string('image');
      $table->integer('price');
    });
  }

  public function down() {
    Capsule::schema()->drop('courses');
  }
}
