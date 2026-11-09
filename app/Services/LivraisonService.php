<?php

namespace App\Services;

use App\Models\Article;
use App\Models\CommandeClient;
use App\Models\DemandeChantier;
use App\Models\LigneCommande;
use App\Models\LigneDemande;
use App\Models\Livraison;
use App\Support\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LivraisonService
{
    private const STATUTS_COMMANDE = ['en_preparation', 'partiellement_livree'];
    private const STATUTS_DEMANDE = ['en_preparation', 'partiellement_satisfaite'];

    public function __construct(
        private MouvementService $mouvements,
        private CommandeService $commandes,
        private DemandeService $demandes,
    ) {}

    /** Commandes et demandes pour lesquelles il reste des quantités à livrer. */
    public function sourcesEligibles(): array
    {
        $sources = [];

        CommandeClient::with(['client:id,nom', 'lignes.article.unite'])
            ->whereIn('statut', self::STATUTS_COMMANDE)->orderBy('id')->get()
            ->each(function (CommandeClient $c) use (&$sources) {
                $lignes = $this->lignesRestantes('commande', $c);
                if ($lignes) {
                    $sources[] = [
                        'type' => 'commande', 'id' => $c->id,
                        'label' => "{$c->numero} - {$c->client->nom}",
                        'adresse' => $c->adresse_destination,
                        'lignes' => $lignes,
                    ];
                }
            });

        DemandeChantier::with(['chantier:id,nom,localisation', 'lignes.article.unite'])
            ->whereIn('statut', self::STATUTS_DEMANDE)->orderBy('id')->get()
            ->each(function (DemandeChantier $d) use (&$sources) {
                $lignes = $this->lignesRestantes('demande', $d);
                if ($lignes) {
                    $sources[] = [
                        'type' => 'demande', 'id' => $d->id,
                        'label' => "{$d->numero} - {$d->chantier->nom}",
                        'adresse' => $d->chantier->localisation ?? '',
                        'lignes' => $lignes,
                    ];
                }
            });

        return $sources;
    }

    public function planifier(array $data): Livraison
    {
        return DB::transaction(function () use ($data) {
            $type = $data['source_type'];
            $source = $this->verrouillerSource($type, (int) $data['source_id']);
            $restants = $this->restants($type, $source);

            foreach ($data['lignes'] as $l) {
                $restant = $restants[(int) $l['article_id']] ?? 0;

                if ((int) $l['qte_prevue'] > $restant) {
                    $ref = Article::whereKey($l['article_id'])->value('reference');

                    throw ValidationException::withMessages([
                        'statut' => "{$ref} : {$restant} restant(s) à planifier, {$l['qte_prevue']} demandé(s).",
                    ]);
                }
            }

            $livraison = Livraison::create([
                'numero' => 'TMP-' . Str::uuid(),
                'commande_id' => $type === 'commande' ? $source->id : null,
                'demande_id' => $type === 'demande' ? $source->id : null,
                'livreur_id' => $data['livreur_id'],
                'adresse_destination' => $data['adresse_destination'],
                'date_prevue' => $data['date_prevue'],
                'statut' => 'planifiee',
                'observation' => $data['observation'] ?? null,
            ]);

            $livraison->update(['numero' => sprintf('LIV-%s-%04d', now()->format('Y'), $livraison->id)]);

            foreach ($data['lignes'] as $l) {
                $livraison->lignes()->create([
                    'article_id' => $l['article_id'],
                    'entrepot_id' => $l['entrepot_id'],
                    'qte_prevue' => (int) $l['qte_prevue'],
                ]);
            }

            Journal::log('livraison_planifiee', 'livraison', $livraison->id, "{$livraison->numero} ({$type} #{$source->id})");

            return $livraison;
        });
    }

    public function demarrer(Livraison $livraison): void
    {
        DB::transaction(function () use ($livraison) {
            $l = $this->verrouiller($livraison, ['planifiee']);

            $l->update(['statut' => 'en_cours']);
            Journal::log('livraison_en_cours', 'livraison', $l->id, $l->numero);
        });
    }

    /**
     * Confirme la remise : enregistre les quantités réellement livrées, sort le stock
     * pour ces quantités, met à jour la commande ou la demande. Tout ou rien.
     */
    public function confirmer(Livraison $livraison, array $data): void
    {
        DB::transaction(function () use ($livraison, $data) {
            $l = $this->verrouiller($livraison, ['planifiee', 'en_cours']);

            $saisies = collect($data['lignes'])->keyBy(fn ($x) => (int) $x['id']);
            $lignes = $l->lignes()->with('article:id,reference,nom')->get();

            if ($saisies->count() !== $lignes->count() || $lignes->contains(fn ($x) => ! $saisies->has($x->id))) {
                throw ValidationException::withMessages(['statut' => 'Toutes les lignes de la livraison doivent être renseignées.']);
            }

            $source = $l->commande_id
                ? CommandeClient::whereKey($l->commande_id)->lockForUpdate()->firstOrFail()
                : DemandeChantier::whereKey($l->demande_id)->lockForUpdate()->firstOrFail();

            if ($source->statut === 'annulee') {
                throw ValidationException::withMessages(['statut' => "La commande {$source->numero} est annulée."]);
            }

            $totalPrevu = 0;
            $totalLivre = 0;

            foreach ($lignes as $ligne) {
                $saisie = $saisies[$ligne->id];
                $livre = (int) $saisie['qte_livree'];
                $motif = trim((string) ($saisie['motif_ecart'] ?? ''));
                $ref = $ligne->article->reference;

                if ($livre > $ligne->qte_prevue) {
                    throw ValidationException::withMessages(['statut' => "{$ref} : impossible de livrer plus que le prévu ({$ligne->qte_prevue})."]);
                }

                if ($livre < $ligne->qte_prevue && $motif === '') {
                    throw ValidationException::withMessages(['statut' => "{$ref} : un motif est obligatoire pour l'écart de livraison."]);
                }

                $ligne->update(['qte_livree' => $livre, 'motif_ecart' => $motif ?: null]);
                $totalPrevu += $ligne->qte_prevue;
                $totalLivre += $livre;

                if ($livre === 0) {
                    continue; // rien n'a quitté le dépôt : aucun mouvement de stock
                }

                try {
                    $this->mouvements->sortie([
                        'article_id' => $ligne->article_id,
                        'entrepot_id' => $ligne->entrepot_id,
                        'quantite' => $livre,
                        'motif' => "Livraison {$l->numero}",
                    ], 'livraison', $l->id);
                } catch (ValidationException $e) {
                    $message = collect($e->errors())->flatten()->first();

                    throw ValidationException::withMessages(['statut' => "{$ref} : {$message}"]);
                }

                $this->crediterSource($l, $ligne->article_id, $livre);
            }

            $statut = $totalLivre === 0 ? 'echouee' : ($totalLivre >= $totalPrevu ? 'livree' : 'partiellement_livree');

            $l->update([
                'statut' => $statut,
                'date_effective' => now()->toDateString(),
                'observation' => $data['observation'] ?? $l->observation,
            ]);

            $l->commande_id
                ? $this->commandes->recalculerStatut(CommandeClient::findOrFail($l->commande_id))
                : $this->demandes->recalculerStatut(DemandeChantier::findOrFail($l->demande_id));

            Journal::log("livraison_{$statut}", 'livraison', $l->id, "{$l->numero} : {$totalLivre}/{$totalPrevu} livré(s)");
        });
    }

    /** Reste à planifier par article = commandé - déjà livré - déjà planifié (livraisons non terminées). */
    public function restants(string $type, Model $source): array
    {
        $champ = $type === 'commande' ? 'quantite_livree' : 'quantite_servie';
        $cle = $type === 'commande' ? 'commande_id' : 'demande_id';

        $planifie = DB::table('lignes_livraison')
            ->join('livraisons', 'livraisons.id', '=', 'lignes_livraison.livraison_id')
            ->where("livraisons.{$cle}", $source->id)
            ->whereIn('livraisons.statut', ['planifiee', 'en_cours'])
            ->selectRaw('lignes_livraison.article_id, SUM(lignes_livraison.qte_prevue) as total')
            ->groupBy('lignes_livraison.article_id')
            ->pluck('total', 'article_id');

        return $source->lignes->mapWithKeys(fn ($l) => [
            $l->article_id => max(0, (int) $l->quantite - (int) $l->{$champ} - (int) ($planifie[$l->article_id] ?? 0)),
        ])->all();
    }

    private function lignesRestantes(string $type, Model $source): array
    {
        $restants = $this->restants($type, $source);

        return $source->lignes
            ->filter(fn ($l) => ($restants[$l->article_id] ?? 0) > 0)
            ->map(fn ($l) => [
                'article_id' => $l->article_id,
                'article' => $l->article->nom,
                'reference' => $l->article->reference,
                'unite' => $l->article->unite->code,
                'restant' => $restants[$l->article_id],
            ])->values()->all();
    }

    private function crediterSource(Livraison $l, int $articleId, int $quantite): void
    {
        if ($l->commande_id) {
            LigneCommande::where('commande_id', $l->commande_id)->where('article_id', $articleId)
                ->increment('quantite_livree', $quantite);
        } else {
            LigneDemande::where('demande_id', $l->demande_id)->where('article_id', $articleId)
                ->increment('quantite_servie', $quantite);
        }
    }

    private function verrouillerSource(string $type, int $id): Model
    {
        if ($type === 'commande') {
            $s = CommandeClient::whereKey($id)->lockForUpdate()->firstOrFail();
            $ok = in_array($s->statut, self::STATUTS_COMMANDE, true);
        } else {
            $s = DemandeChantier::whereKey($id)->lockForUpdate()->firstOrFail();
            $ok = in_array($s->statut, self::STATUTS_DEMANDE, true);
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                'statut' => "Planification impossible : {$s->numero} doit être « En préparation » (ou partiellement traitée).",
            ]);
        }

        return $s;
    }

    private function verrouiller(Livraison $livraison, array $attendus): Livraison
    {
        $l = Livraison::whereKey($livraison->id)->lockForUpdate()->firstOrFail();

        if (! in_array($l->statut, $attendus, true)) {
            $actuel = Livraison::STATUTS[$l->statut] ?? $l->statut;

            throw ValidationException::withMessages([
                'statut' => "Action impossible : la livraison {$l->numero} est « {$actuel} ».",
            ]);
        }

        return $l;
    }
}