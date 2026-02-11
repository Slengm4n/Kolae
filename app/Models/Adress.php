<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;  
class Adress extends Model
{
    use HasFactory;

    protected $fillable = [
      'cep',
      'street',
      'number',
      'neighborhood',
      'complement',
      'city',
      'state',
      'latitude',
      'longitude',  
    ];

    public function venue()
    {
        return $this->hasOne(Venue::class);
    }
}
