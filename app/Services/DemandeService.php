<?php

namespace App\Services;

use App\Models\DemandeChantier;
use App\Models\User;
use App\Support\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemandeService
{
    public function creer(array $data, User $user): DemandeChantier
    {
        return DB::transaction(function () use ($data, $user) {
            $demande = DemandeChantier::create([
                'numero' => 'TMP-' . Str::uuid(),
                'chantier_id' => $data['chantier_id'],
                'demandeur_id' => $user->id,
                'statut' => 'en_attente',
                'motif' => $data['motif'],
                'date_souhaitee' => $data['date_souhaitee'] ?? null,
            ]);

            $demande->update(['numero' => sprintf('DEM-%s-%04d', now()->format('Y'), $demande->id)]);

            foreach ($data['lignes'] as $ligne) {
                $demande->lignes()->create([
                    'article_id' => $ligne['article_id'],
                    'quantite' => (int) $ligne['quantite'],
                ]);
            }

            Journal::log('demande_soumise', 'demande', $demande->id, $demande->numero);

            return $demande;
        });
    }

    public function approuver(DemandeChantier $demande, User $validateur): void
    {
        DB::transaction(function () use ($demande, $validateur) {
            $d = $this->verrouiller($demande, 'en_attente');
            $this->verifierSeparation($d, $validateur);

            $d->update(['statut' => 'approuvee', 'validateur_id' => $validateur->id, 'motif_refus' => null]);
            Journal::log('demande_approuvee', 'demande', $d->id, $d->numero);
        });
    }

    public function refuser(DemandeChantier $demande, User $validateur, string $motif): void
    {
        DB::transaction(function () use ($demande, $validateur, $motif) {
            $d = $this->verrouiller($demande, 'en_attente');
            $this->verifierSeparation($d, $validateur);

            $d->update(['statut' => 'refusee', 'validateur_id' => $validateur->id, 'motif_refus' => $motif]);
            Journal::log('demande_refusee', 'demande', $d->id, "{$d->numero} : {$motif}");
        });
    }

    public function preparer(DemandeChantier $demande): void
    {
        DB::transaction(function () use ($demande) {
            $d = $this->verrouiller($demande, 'approuvee');

            $d->update(['statut' => 'en_preparation']);
            Journal::log('demande_en_preparation', 'demande', $d->id, $d->numero);
        });
    }

    /**
     * Appelé par les livraisons (étape 9) après chaque remise confirmée :
     * satisfaite si tout est servi, partiellement satisfaite sinon.
     */
    public function recalculerStatut(DemandeChantier $demande): void
    {
        $lignes = $demande->lignes()->get();

        if ($lignes->every(fn ($l) => $l->quantite_servie === 0)) {
            return;
        }

        $statut = $lignes->every(fn ($l) => $l->quantite_servie >= $l->quantite)
            ? 'satisfaite'
            : 'partiellement_satisfaite';

        $demande->update(['statut' => $statut]);
        Journal::log("demande_{$statut}", 'demande', $demande->id, $demande->numero);
    }

    /** Verrouille la ligne et vérifie qu'elle est bien dans l'état attendu. */
    private function verrouiller(DemandeChantier $demande, string $attendu): DemandeChantier
    {
        $d = DemandeChantier::whereKey($demande->id)->lockForUpdate()->firstOrFail();

        if ($d->statut !== $attendu) {
            $actuel = DemandeChantier::STATUTS[$d->statut] ?? $d->statut;

            throw ValidationException::withMessages([
                'statut' => "Action impossible : la demande {$d->numero} est « {$actuel} ».",
            ]);
        }

        return $d;
    }

    private function verifierSeparation(DemandeChantier $d, User $validateur): void
    {
        if ($d->demandeur_id === $validateur->id) {
            throw ValidationException::withMessages([
                'statut' => 'Vous ne pouvez pas valider votre propre demande.',
            ]);
        }
    }
}