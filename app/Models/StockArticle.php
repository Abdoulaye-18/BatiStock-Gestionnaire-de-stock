<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockArticle extends Model
{
    protected $fillable = ['article_id', 'entrepot_id', 'quantite'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function entrepot(): BelongsTo
    {
        return $this->belongsTo(Entrepot::class);
    }
}