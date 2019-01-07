<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $table = 'lesson';
    protected $fillable = ['date', 'period'];

    public function records()
    {
        return $this->hasMany('App\Record');
    }
}
