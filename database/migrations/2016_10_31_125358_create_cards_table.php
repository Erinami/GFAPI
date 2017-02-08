<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->integer('card_id');
            $table->integer('set_id');
            $table->integer('set_position');
            $table->integer('card_stat_display');
            $table->string('image_name');
            $table->string('card_attribute');
            $table->integer('cost');
            $table->string('card_description');
            $table->string('card_description_eng')->nullable();
            $table->integer('disposal');
            $table->integer('before_evolution_uid')->nullable();
            $table->integer('evolution_uid')->nullable();
            $table->integer('max_level');
            $table->integer('initial_level');
            $table->string('skill_name');
            $table->string('skill_name_eng')->nullable();
            $table->string('card_name');
            $table->string('card_name_eng')->nullable();
            $table->string('rarity');
            $table->integer('strongest_level');
            $table->string('skill_description');
            $table->string('skill_description_eng')->nullable();
            $table->integer('initial_attack_base')->nullable();
            $table->integer('initial_defense_base')->nullable();
            $table->integer('max_attack_base')->nullable();
            $table->integer('max_defense_base')->nullable();
            $table->integer('macarons')->nullable();
            $table->string('card_type')->nullable();
            $table->boolean('ringable')->nullable();
            $table->timestamps();
            $table->primary('card_id');
            $table->foreign('set_id')->references('set_id')->on('sets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cards');
    }
}
