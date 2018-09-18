<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Courses extends Migration {
    /**
     * Do the migration
     */
    public function up() {
        Capsule::schema()->create('courses', function(Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('description');
            $table->string('image');
            $table->integer('price');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('courses');
    }
}