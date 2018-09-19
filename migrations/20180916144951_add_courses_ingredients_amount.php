<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddCoursesIngredientsAmount extends Migration {
  public function up() {
    Capsule::schema()->table('courses_ingredients', function(Blueprint $table) {
      $table->float('amount')->default(0);
    });
  }

  public function down() {
    Capsule::schema()->table('courses_ingredients', function(Blueprint $table) {
      $table->dropColumn('amount');
    });
  }
}
