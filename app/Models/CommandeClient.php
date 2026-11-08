<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandeClient extends Model
{
    protected $table = 'commandes_client';

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'confirmee' => 'Confirmée',
        'en_preparation' => 'En préparation',
        'partiellement_livree' => 'Partiellement livrée',
        'livree' => 'Livrée',
        'annulee' => 'Annulée',
    ];

    protected $fillable = [
        'numero', 'client_id', 'user_id', 'date_commande',
        'statut', 'adresse_destination', 'montant_total',
    ];

    protected $casts = ['date_commande' => 'date'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'commande_id');
    }
}