<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuari extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'login', 'password', 'avatar', 'wins', 'games'];

    public function jugadors()
    {
        return $this->hasMany(Jugador::class, "skfUser_id");
    }
}
