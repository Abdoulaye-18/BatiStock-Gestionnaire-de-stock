<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}

