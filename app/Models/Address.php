<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;  
class Address extends Model
{
    protected $table = 'addresses';
    protected $fillable = ['cep', 'id_street', 'numbe', 'complement', 'latitude', 'longitude'];
    
    public function street()
    {
        return $this->belongsToo(Street::class, 'id_street');
    }
}
