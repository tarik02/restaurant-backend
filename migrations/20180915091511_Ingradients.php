<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Ingradients extends Migration {
    /**
     * Do the migration
     */
    public function up() {
        Capsule::schema()->create('ingradients', function(Blueprint $table) {
            $table->increments('id');
            $table->string('title');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('ingradients');
    }
}