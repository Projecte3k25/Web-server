<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Okupa extends Model
{
    use HasFactory;

    protected $fillable = ['pais_id', 'player_id', 'tropes'];

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function jugador()
    {
        return $this->belongsTo(Jugador::class, 'player_id');
    }
}
