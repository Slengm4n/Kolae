<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country';
    public $timestamps = false;
    protected $fillable = ['name'];

    public function country()
    {
        return $this->hasMany(State::class, 'id_country');
    }
}
