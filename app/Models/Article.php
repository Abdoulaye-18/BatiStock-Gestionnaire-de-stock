<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'reference', 'nom', 'categorie_id', 'unite_id',
        'prix_vente', 'seuil_alerte', 'actif',
    ];

    protected $casts = ['actif' => 'boolean'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(Unite::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(StockArticle::class);
    }

    /** Articles dont le stock total est inférieur au seuil d'alerte */
    public function scopeSousSeuil(Builder $q): Builder
    {
        return $q->whereRaw(
            'COALESCE((SELECT SUM(quantite) FROM stock_articles WHERE stock_articles.article_id = articles.id), 0) < articles.seuil_alerte'
        );
    }

    public static function statutStock(int $quantite, int $seuil): string
    {
        return $quantite <= 0 ? 'rupture' : ($quantite < $seuil ? 'faible' : 'ok');
    }
}