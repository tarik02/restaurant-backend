<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddCoursesVisible extends Migration {
  public function up() {
    Capsule::schema()->table('courses', function(Blueprint $table) {
      $table->boolean('visible')->default(true);
    });
  }
  
  public function down() {
    Capsule::schema()->table('courses', function(Blueprint $table) {
      $table->dropColumn('visible');
    });
  }
}
