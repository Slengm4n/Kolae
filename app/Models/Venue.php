<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'adress_id',
        'name',
        'avarage_price_per_hour',
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
        'avarage_price_per_hour' => 'decimal:2'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class); 
    }

    public function images()
    {
        return $this->hasMany(VenueImage::class);
    }
}
