<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $table = 'cards';
    protected $fillable = [
        'card_id', 'set_id', 'card_set_position', 'card_stat_display', 'card_image_name', 'card_attribute',
        'card_cost', 'card_description', 'card_description_eng', 'card_disposal', 'card_before_evolution_uid',
        'card_evolution_uid', 'card_max_level', 'card_initial_level', 'card_skill_name', 'card_skill_name_eng',
        'card_name', 'card_name_eng', 'card_rarity', 'card_strongest_level', 'card_skill_description',
        'card_skill_description_eng', 'card_initial_attack_base', 'card_initial_defense_base',
        'card_max_attack_base', 'card_max_defense_base', 'card_macarons', 'card_type', 'card_ringable'
    ];
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    public $incrementing = false;
    protected $primaryKey = 'card_id';

    public function girls() {
        return $this->belongsToMany('App\Girl', 'girls_cards', 'card_id', 'girl_id');
    }

    public function set()
    {
        return $this->belongsTo('App\Set', 'set_id');
    }

}
