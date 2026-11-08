<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneCommande extends Model
{
    protected $table = 'lignes_commande';

    protected $fillable = ['commande_id', 'article_id', 'quantite', 'quantite_livree', 'prix_unitaire'];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(CommandeClient::class, 'commande_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}