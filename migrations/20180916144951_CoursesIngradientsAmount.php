<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CoursesIngradientsAmount extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('courses_ingradients', function(Blueprint $table) {
      $table->float('amount')->default(0);
    });
  }

  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('courses_ingradients', function(Blueprint $table) {
      $table->dropColumn('amount');
    });
  }
}
