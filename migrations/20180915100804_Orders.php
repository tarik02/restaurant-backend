<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Orders extends Migration {
    /**
     * Do the migration
     */
    public function up() {
        Capsule::schema()->create('orders', function(Blueprint $table) {
            $table->increments('id');
            $table->string('contact_name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->float('latitude');
            $table->float('longtitude');
            $table->string('notes');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        Capsule::schema()->drop('orders');
    }
}