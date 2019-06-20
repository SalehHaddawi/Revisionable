<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('model')->nullable();
            $table->integer('model_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('event');
            $table->string('key')->nullable();
            $table->mediumText('desc')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->ipAddress('ip')->nullable();

            $table->timestamps();


            $table->index(array('model_id', 'model'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('revisions');
    }
}

