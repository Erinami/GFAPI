<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGirlsCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('girls_cards', function (Blueprint $table) {
            $table->integer('card_id');
            $table->integer('girl_id');
            $table->timestamps();
            $table->primary(['girl_id','card_id']);
            $table->foreign('card_id')->references('card_id')->on('cards')->onDelete('cascade');
            $table->foreign('girl_id')->references('girl_id')->on('girls')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('girls_cards');
    }
}
