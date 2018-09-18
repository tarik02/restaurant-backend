<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CourseVisible extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('courses', function(Blueprint $table) {
      $table->boolean('visible')->default(true);
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('courses', function(Blueprint $table) {
      $table->dropColumn('visible');
    });
  }
}
