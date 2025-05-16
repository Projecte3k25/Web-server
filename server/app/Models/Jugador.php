<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jugador extends Model
{
    use HasFactory;

    
    protected $fillable = ['skfUser_id', 'skfPartida_id', 'skfNumero'];

    public function usuari()
    {
        return $this->belongsTo(Usuari::class, 'skfUser_id');
    }

    public function partida()
    {
        return $this->belongsTo(Partida::class, 'skfPartida_id');
    }

    public function cartes()
    {
        return $this->belongsToMany(Carta::class, 'm_a_s', 'jugador_id', 'carta_id');
    }

    public function okupes()
    {
        return $this->hasMany(Okupa::class, 'player_id');
    }
}
