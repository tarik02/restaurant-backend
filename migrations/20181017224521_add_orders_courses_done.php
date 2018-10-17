<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddOrdersCoursesDone extends Migration {
  public function up() {
    Capsule::schema()->table('orders_courses', function(Blueprint $table) {
      $table->integer('done')->default(0);
    });
  }

  public function down() {
    Capsule::schema()->table('orders_courses', function(Blueprint $table) {
      $table->dropColumn('done');
    });
  }
}
