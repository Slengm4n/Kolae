<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Neighborhood extends Model
{
    protected $table = 'neighborhood';
    public $timestamps = false;
    protected $fillable = ['name', 'id_city'];

    public function city()
    {
        return $this->belongsToo(City::class, 'id_city');
    }

    public function streets()
    {
        return $this->hasMany(Stree::class, 'id_neighborhood');
    }
}
