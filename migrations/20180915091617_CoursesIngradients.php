<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CoursesIngradients extends Migration {
    /**
     * Do the migration
     */
    public function up() {
        Capsule::schema()->create('courses_ingradients', function(Blueprint $table) {
            $table->integer('course_id');
            $table->integer('ingradient_id');
            $table->integer('count');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('courses_ingradients');
    }
}