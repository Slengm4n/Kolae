<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class VenueImage extends Model
{
    use HasFactory;
    
    protected $table = 'venues_images';

    protected $fillable = ['venue_id', 'file_path'];

    /**
     * Relacionamento: A imagem pertence a um local (Venue)
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}