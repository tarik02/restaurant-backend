<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateReviews extends Migration {
  public function up() {
    Capsule::schema()->create('reviews', function(Blueprint $table) {
      $table->increments('id');

      $table->string('source_type')->nullable();
      $table->integer('source_id')->nullable();

      $table->string('target_type')->nullable();
      $table->integer('target_id')->nullable();

      $table->integer('user_id')->nullable();

      $table->text('text')->nullable();
      $table->integer('rating')->nullable();

      $table->timestamp('created_at');
    });
  }

  public function down() {
    Capsule::schema()->drop('reviews');
  }
}
