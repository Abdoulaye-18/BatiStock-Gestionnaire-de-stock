<?php

namespace App\Notifications;

use App\Models\DemandeChantier;
use App\Models\User;
use Illuminate\Notifications\Notification;

class NotificationDemande extends Notification
{
    /** $evenement : a_preparer (gestionnaire) | approuvee | refusee (demandeur) */
    public function __construct(
        private DemandeChantier $demande,
        private string $evenement,
        private User $validateur,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $d = $this->demande;
        $chantier = $d->chantier->nom;

        [$titre, $message, $statut] = match ($this->evenement) {
            'a_preparer' => [
                'Demande à préparer',
                "La demande {$d->numero} ({$chantier}) a été approuvée par {$this->validateur->name}. Passez-la en préparation.",
                'approuvee',
            ],
            'approuvee' => [
                'Demande approuvée',
                "Votre demande {$d->numero} ({$chantier}) a été approuvée.",
                null,
            ],
            'refusee' => [
                'Demande refusée',
                "Votre demande {$d->numero} ({$chantier}) a été refusée : {$d->motif_refus}",
                null,
            ],
        };

        return [
            'evenement' => $this->evenement,
            'titre' => $titre,
            'message' => $message,
            'demande_id' => $d->id,
            'numero' => $d->numero,
            // chemin interne uniquement (pas de nom de domaine enregistré)
            'lien' => route('demandes.index', $statut ? ['statut' => $statut] : [], false),
        ];
    }
}