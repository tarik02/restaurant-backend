<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateCoursesIngredients extends Migration {
  public function up() {
    Capsule::schema()->create('courses_ingredients', function(Blueprint $table) {
      $table->integer('course_id');
      $table->integer('ingredient_id');
      $table->integer('count');
    });
  }

  public function down() {
    Capsule::schema()->drop('courses_ingredients');
  }
}
