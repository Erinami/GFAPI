<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sets', function (Blueprint $table) {
            $table->integer('set_id');
            $table->string('set_type');
            $table->string('set_name_initial');
            $table->string('set_name_initial_eng')->nullable();
            $table->string('set_name_final');
            $table->string('set_name_final_eng')->nullable();
            $table->timestamps();
            $table->primary('set_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sets');
    }
}
