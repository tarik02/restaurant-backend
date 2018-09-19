<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateCoursesImages extends Migration {
  public function up() {
    Capsule::schema()->create('courses_images', function(Blueprint $table) {
      $table->integer('course_id');
      $table->string('src');
    });
  }

  public function down() {
    Capsule::schema()->drop('courses_images');
  }
}
