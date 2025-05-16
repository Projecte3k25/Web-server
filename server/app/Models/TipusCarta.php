<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipusCarta extends Model
{
    use HasFactory;
    
    protected $fillable = ['nom'];

    public function cartes()
    {
        return $this->hasMany(Carta::class, 'tipus');
    }
}
