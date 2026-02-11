<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
class VenueImage extends Model
{
    use HasFactory;
    
    protected $fillable = ['venue_id', 'file_path'];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
