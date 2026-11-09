<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneLivraison extends Model
{
    protected $table = 'lignes_livraison';

    protected $fillable = ['livraison_id', 'article_id', 'entrepot_id', 'qte_prevue', 'qte_livree', 'motif_ecart'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function entrepot(): BelongsTo
    {
        return $this->belongsTo(Entrepot::class);
    }
}