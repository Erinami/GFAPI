<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Girl extends Model
{
    protected $table = 'girls';
    protected $fillable = [
        'girl_id', 'girl_name_official_eng', 'girl_name_romanization_eng', 'girl_name', 'girl_image_name', 'girl_age', 'girl_authority',
        'girl_birthday_date', 'girl_birthday', 'girl_blood', 'girl_bust', 'girl_class_name', 'girl_club', 'girl_club_eng', 'girl_cv', 'girl_cv_eng',
        'girl_description', 'girl_description_eng', 'girl_favorite_food', 'girl_favorite_food_eng', 'girl_favorite_subject', 'girl_favorite_subject_eng',
        'girl_attribute', 'girl_hated_food', 'girl_hated_food_eng', 'girl_height', 'girl_hip', 'girl_hobby', 'girl_horoscope', 'girl_horoscope_eng', 'girl_name_hiragana',
        'girl_nickname', 'girl_nickname_eng', 'girl_school', 'girl_school_eng', 'girl_tweet_name', 'girl_waist', 'girl_weight', 'girl_year'
    ];
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    public $incrementing = false;
    protected $primaryKey = 'girl_id';

    public function cards() {
        return $this->belongsToMany('App\Card', 'girls_cards', 'girl_id', 'card_id');
    }
    
}
