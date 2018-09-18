<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class OrdersCourses extends Migration {
    /**
     * Do the migration
     */
    public function up() {
        Capsule::schema()->create('orders_courses', function(Blueprint $table) {
            $table->integer('order_id');
            $table->integer('course_id');
            $table->integer('count');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('orders_courses');
    }
}