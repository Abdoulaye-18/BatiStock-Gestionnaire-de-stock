<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entrepot extends Model
{
    protected $fillable = ['code', 'nom', 'localisation'];

    public function stocks(): HasMany
    {
        return $this->hasMany(StockArticle::class);
    }
}