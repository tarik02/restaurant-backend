<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateOrders extends Migration {
  public function up() {
    Capsule::schema()->create('orders', function(Blueprint $table) {
      $table->increments('id');
      $table->string('contact_name');
      $table->string('phone')->nullable();
      $table->string('address')->nullable();
      $table->double('latitude');
      $table->double('longtitude');
      $table->string('notes');
    });
  }

  public function down() {
    Capsule::schema()->drop('orders');
  }
}
