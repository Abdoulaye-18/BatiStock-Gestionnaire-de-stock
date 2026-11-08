<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneDemande extends Model
{
    protected $table = 'lignes_demande';

    protected $fillable = ['demande_id', 'article_id', 'quantite', 'quantite_servie'];

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeChantier::class, 'demande_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}