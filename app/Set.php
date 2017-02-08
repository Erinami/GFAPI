<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    protected $table = 'sets';
    protected $fillable = [
        'set_id', 'set_type', 'set_name_initial', 'set_name_initial_eng', 'set_name_final', 'set_name_final_eng', 'set_image_name_initial', 'set_image_name_final'
    ];
    protected $hidden = [
        'created_at', 'updated_at'
    ];
    public $incrementing = false;
    protected $primaryKey = 'set_id';

    public function cards() {
        return $this->hasMany('App\Card', 'set_id');
    }

    public function set_size() {
        return $this->hasOne('App\Card')->selectRaw('count(*) as cards_count')->groupBy('set_id');
    }

}
