<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Continent extends Model
{
    use HasFactory;
    protected $fillable = ['nom', 'reforc_tropes'];
    
    public function paisos() {
        return $this->hasMany(Pais::class);
    }

}
