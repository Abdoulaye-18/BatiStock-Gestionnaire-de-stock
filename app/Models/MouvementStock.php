<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MouvementStock extends Model
{
    protected $table = 'mouvements_stock';

    protected $fillable = [
        'stock_article_id', 'user_id', 'fournisseur_id', 'type', 'quantite',
        'motif', 'date_mouvement', 'id_transfert', 'origine_type', 'origine_id',
    ];

    protected $casts = ['date_mouvement' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un mouvement validé ne peut pas être modifié.'));
        static::deleting(fn () => throw new \LogicException('Un mouvement validé ne peut pas être supprimé.'));
    }

    public function stock(): BelongsTo
{
    return $this->belongsTo(StockArticle::class, 'stock_article_id');
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function fournisseur(): BelongsTo
{
    return $this->belongsTo(Fournisseur::class);
}
}

