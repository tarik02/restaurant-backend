<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class RemoveCoursesIngredientsCount extends Migration {
  public function up() {
    Capsule::schema()->table('courses_ingredients', function(Blueprint $table) {
      $table->dropColumn('count');
    });
  }

  public function down() {
    Capsule::schema()->table('courses_ingredients', function(Blueprint $table) {
      $table->integer('count')->default(0);
    });
  }
}
