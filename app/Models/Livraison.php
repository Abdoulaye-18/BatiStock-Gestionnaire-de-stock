<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Livraison extends Model
{
    protected $table = 'livraisons';

    public const STATUTS = [
        'planifiee' => 'Planifiée',
        'en_cours' => 'En cours',
        'livree' => 'Livrée',
        'partiellement_livree' => 'Partiellement livrée',
        'echouee' => 'Échouée',
    ];

    protected $fillable = [
        'numero', 'commande_id', 'demande_id', 'livreur_id', 'adresse_destination',
        'date_prevue', 'date_effective', 'statut', 'observation',
    ];

    protected $casts = ['date_prevue' => 'date', 'date_effective' => 'date'];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(CommandeClient::class, 'commande_id');
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeChantier::class, 'demande_id');
    }

    public function livreur(): BelongsTo
    {
        return $this->belongsTo(Livreur::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneLivraison::class, 'livraison_id');
    }
}