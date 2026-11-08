<?php

namespace App\Services;

use App\Models\Article;
use App\Models\CommandeClient;
use App\Models\User;
use App\Support\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommandeService
{
    public function creer(array $data, User $user): CommandeClient
    {
        return DB::transaction(function () use ($data, $user) {
            $commande = CommandeClient::create([
                'numero' => 'TMP-' . Str::uuid(),
                'client_id' => $data['client_id'],
                'user_id' => $user->id,
                'date_commande' => $data['date_commande'] ?? now()->toDateString(),
                'statut' => 'brouillon',
                'adresse_destination' => $data['adresse_destination'],
            ]);

            $commande->update(['numero' => sprintf('CMD-%s-%04d', now()->format('Y'), $commande->id)]);
            $this->remplacerLignes($commande, $data['lignes']);

            Journal::log('commande_creee', 'commande', $commande->id, $commande->numero);

            return $commande;
        });
    }

    public function modifier(CommandeClient $commande, array $data): void
    {
        DB::transaction(function () use ($commande, $data) {
            $c = $this->verrouiller($commande, 'brouillon');

            $c->update([
                'client_id' => $data['client_id'],
                'adresse_destination' => $data['adresse_destination'],
                'date_commande' => $data['date_commande'] ?? $c->date_commande,
            ]);
            $this->remplacerLignes($c, $data['lignes']);

            Journal::log('commande_modifiee', 'commande', $c->id, $c->numero);
        });
    }

    public function confirmer(CommandeClient $commande): void
    {
        DB::transaction(function () use ($commande) {
            $c = $this->verrouiller($commande, 'brouillon');

            $c->update(['statut' => 'confirmee']);
            Journal::log('commande_confirmee', 'commande', $c->id, $c->numero);
        });
    }

    public function preparer(CommandeClient $commande): void
    {
        DB::transaction(function () use ($commande) {
            $c = $this->verrouiller($commande, 'confirmee');

            $c->update(['statut' => 'en_preparation']);
            Journal::log('commande_en_preparation', 'commande', $c->id, $c->numero);
        });
    }

    public function annuler(CommandeClient $commande, string $motif): void
    {
        DB::transaction(function () use ($commande, $motif) {
            $c = $this->verrouiller($commande, ['brouillon', 'confirmee', 'en_preparation']);

            $c->update(['statut' => 'annulee']);
            Journal::log('commande_annulee', 'commande', $c->id, "{$c->numero} : {$motif}");
        });
    }

    /**
     * Appelé par les livraisons (étape 9) après chaque remise confirmée :
     * livrée si tout est livré, partiellement livrée sinon.
     */
    public function recalculerStatut(CommandeClient $commande): void
    {
        if ($commande->statut === 'annulee') {
            return;
        }

        $lignes = $commande->lignes()->get();

        if ($lignes->every(fn ($l) => (int) $l->quantite_livree === 0)) {
            return;
        }

        $statut = $lignes->every(fn ($l) => (int) $l->quantite_livree >= (int) $l->quantite)
            ? 'livree'
            : 'partiellement_livree';

        $commande->update(['statut' => $statut]);
        Journal::log("commande_{$statut}", 'commande', $commande->id, $commande->numero);
    }

    /** Remplace les lignes ; le montant est TOUJOURS recalculé par le serveur. */
    private function remplacerLignes(CommandeClient $commande, array $lignes): void
    {
        $commande->lignes()->delete();
        $total = 0;

        foreach ($lignes as $l) {
            $quantite = (int) $l['quantite'];
            $prix = (float) ($l['prix_unitaire'] ?? Article::whereKey($l['article_id'])->value('prix_vente') ?? 0);

            $commande->lignes()->create([
                'article_id' => $l['article_id'],
                'quantite' => $quantite,
                'prix_unitaire' => $prix,
            ]);

            $total += $quantite * $prix;
        }

        $commande->update(['montant_total' => round($total, 2)]);
    }

    /** Verrouille la ligne et vérifie qu'elle est dans l'un des états attendus. */
    private function verrouiller(CommandeClient $commande, string|array $attendus): CommandeClient
    {
        $c = CommandeClient::whereKey($commande->id)->lockForUpdate()->firstOrFail();

        if (! in_array($c->statut, (array) $attendus, true)) {
            $actuel = CommandeClient::STATUTS[$c->statut] ?? $c->statut;

            throw ValidationException::withMessages([
                'statut' => "Action impossible : la commande {$c->numero} est « {$actuel} ».",
            ]);
        }

        return $c;
    }
}