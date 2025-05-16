<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carta extends Model
{
    use HasFactory;

    protected $fillable = ['tipus', 'pais_id'];

    public function tipusCarta()
    {
        return $this->belongsTo(TipusCarta::class, 'tipus');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function jugadors()
    {
        return $this->belongsToMany(Jugador::class, 'm_a_s', 'carta_id', 'jugador_id');
    }
}
