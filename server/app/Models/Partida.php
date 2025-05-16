<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partida extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'date', 'nom', 'token', 'max_players', 'admin_id', 'torn_player_id', 'estat_torn',
    ];

    public function admin()
    {
        return $this->belongsTo(Jugador::class, 'admin_id');
    }

    public function estat()
    {
        return $this->belongsTo(Estat::class, 'estat_torn');
    }

    public function jugadors()
    {
        return $this->hasMany(Jugador::class, 'skfPartida_id');
    }
}
