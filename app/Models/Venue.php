<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id',
        'address_id',
        'name',
        'average_price_per_hour',
        'court_capacity',
        'has_leisure_area',
        'leisure_area_capacity',
        'floor_type',
        'has_lighting',
        'is_covered',
        'status',
    ];

    protected $casts = [
        'has_leisure_area' => 'boolean',
        'has_lighting' => 'boolean',
        'is_covered' => 'boolean',
        'average_price_per_hour' => 'decimal:2'
    ];

    /**
     * O dono deste local (Owner)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * O endereço deste local
     */
    public function address()
    {
        return $this->belongsTo(Address::class); 
    }

    /**
     * As imagens deste local
     */
    public function images()
    {
        return $this->hasMany(VenueImage::class, 'venue_id');
    }

    public function games()
    {
        return $this->hasMany(Game::class, 'venue_id');
    }
}