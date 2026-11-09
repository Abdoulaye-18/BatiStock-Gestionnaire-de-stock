<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Livreur extends Model
{
    protected $fillable = ['nom', 'telephone', 'disponible'];

    protected $casts = ['disponible' => 'boolean'];
}