<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGirlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('girls', function (Blueprint $table) {
            $table->integer('girl_id');
            $table->string('name_official_eng');
            $table->string('name_romanization_eng');
            $table->string('name');
            $table->string('image_name');
            $table->integer('age')->nullable();
            $table->string('authority');
            $table->date('birthday_date')->nullable();
            $table->date('birthday')->nullable();
            $table->string('blood')->nullable();
            $table->integer('bust')->nullable();
            $table->string('class_name');
            $table->string('club');
            $table->string('club_eng')->nullable();
            $table->string('cv')->nullable();
            $table->string('cv_eng')->nullable();
            $table->string('description');
            $table->string('description_eng')->nullable();
            $table->string('favorite_food');
            $table->string('favorite_food_eng')->nullable();
            $table->string('favorite_subject')->nullable();
            $table->string('favorite_subject_eng')->nullable();
            $table->string('attribute');
            $table->string('hated_food');
            $table->string('hated_food_eng')->nullable();
            $table->integer('height')->nullable();
            $table->integer('hip')->nullable();
            $table->string('hobby');
            $table->string('hobby_eng')->nullable();
            $table->string('horoscope')->nullable();
            $table->string('horoscope_eng')->nullable();
            $table->string('name_hiragana');
            $table->string('nickname')->nullable();
            $table->string('nickname_eng')->nullable();
            $table->string('school');
            $table->string('school_eng');
            $table->string('tweet_name')->nullable();
            $table->integer('waist')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('year')->nullable();
            $table->timestamps();
            $table->primary('girl_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('girlbirthdays');
    }
}
