<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
    use HasFactory;

    //Model->'Game'/Tabela->'matches'
    protected $table = 'matches';

    protected $fillable = [
        'venue_id',
        'sport_id',
        'creator_user_id',
        'status',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Onde o jogo vai acontecer
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Qual esporte será praticado
     */
    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * O usuário que criou/organizou o jogo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }
}