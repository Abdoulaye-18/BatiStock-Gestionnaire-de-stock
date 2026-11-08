<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandeChantier extends Model
{
    protected $table = 'demandes_chantier';

    public const STATUTS = [
        'en_attente' => 'En attente',
        'approuvee' => 'Approuvée',
        'refusee' => 'Refusée',
        'en_preparation' => 'En préparation',
        'partiellement_satisfaite' => 'Partiellement satisfaite',
        'satisfaite' => 'Satisfaite',
    ];

    protected $fillable = [
        'numero', 'chantier_id', 'demandeur_id', 'validateur_id',
        'statut', 'motif', 'motif_refus', 'date_souhaitee',
    ];

    protected $casts = ['date_souhaitee' => 'date'];

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneDemande::class, 'demande_id');
    }
}