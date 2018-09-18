<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CoursesImages extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->create('courses_images', function(Blueprint $table) {
      $table->integer('course_id');
      $table->string('src');
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->drop('courses_images');
  }
}
