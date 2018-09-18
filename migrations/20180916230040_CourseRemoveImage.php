<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CourseRemoveImage extends Migration {
  /**
   * Do the migration
   */
  public function up() {
    Capsule::schema()->table('courses', function(Blueprint $table) {
      $table->dropColumn('image');
    });
  }
  
  /**
   * Undo the migration
   */
  public function down() {
    Capsule::schema()->table('courses', function(Blueprint $table) {
      $table->string('image');
    });
  }
}
