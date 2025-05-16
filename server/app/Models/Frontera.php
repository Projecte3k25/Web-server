<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frontera extends Model
{
    use HasFactory;
    protected $fillable = [
        'pais1_id',
        'pais2_id',
    ];

    public function pais1()
    {
        return $this->belongsTo(Pais::class, 'pais1_id');
    }

    public function pais2()
    {
        return $this->belongsTo(Pais::class, 'pais2_id');
    }
}
