<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    use HasFactory;


    protected $fillable = ['nom', 'continent_id', 'imatge'];

    public function continent()
    {
        return $this->belongsTo(Continent::class);
    }

    public function carta()
    {
        return $this->hasOne(Carta::class);
    }

    public function okupes()
    {
        return $this->hasMany(Okupa::class, 'pais_id');
    }

    public function fronteres()
    {
        return $this->belongsToMany(Pais::class, 'Fronteras', 'pais1_id', 'pais2_id');
    }
}
