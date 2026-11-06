<?php

namespace App\Services;

use App\Models\Article;
use App\Models\MouvementStock;
use App\Models\StockArticle;
use App\Support\Journal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MouvementService
{
    public function entree(array $d): MouvementStock
    {
        return DB::transaction(fn () => $this->enregistrer(
            articleId: $d['article_id'],
            entrepotId: $d['entrepot_id'],
            delta: (int) $d['quantite'],
            type: 'entree',
            motif: $d['motif'],
            date: $d['date_mouvement'] ?? null,
            fournisseurId: $d['fournisseur_id'] ?? null,
        ));
    }

    /** $origineType / $origineId : utilisés plus tard par demandes, commandes et livraisons. */
    public function sortie(array $d, ?string $origineType = null, ?int $origineId = null): MouvementStock
    {
        return DB::transaction(fn () => $this->enregistrer(
            articleId: $d['article_id'],
            entrepotId: $d['entrepot_id'],
            delta: -(int) $d['quantite'],
            type: 'sortie',
            motif: $d['motif'],
            date: $d['date_mouvement'] ?? null,
            origineType: $origineType,
            origineId: $origineId,
        ));
    }

    /** Quantité signée : positive = ajout, négative = retrait. */
    public function ajustement(array $d): MouvementStock
    {
        return DB::transaction(fn () => $this->enregistrer(
            articleId: $d['article_id'],
            entrepotId: $d['entrepot_id'],
            delta: (int) $d['quantite'],
            type: 'ajustement',
            motif: $d['motif'],
            date: $d['date_mouvement'] ?? null,
        ));
    }

    /** Deux mouvements liés par le même id_transfert, dans UNE transaction. */
    public function transfert(array $d): string
    {
        return DB::transaction(function () use ($d) {
            $idTransfert = (string) Str::uuid();
            $quantite = (int) $d['quantite'];

            // Verrouillage dans un ordre fixe : évite les blocages croisés entre deux transferts
            foreach (collect([$d['entrepot_id'], $d['entrepot_destination_id']])->sort() as $depot) {
                $this->stockVerrouille($d['article_id'], (int) $depot);
            }

            $this->enregistrer(
                articleId: $d['article_id'],
                entrepotId: $d['entrepot_id'],
                delta: -$quantite,
                type: 'transfert_sortie',
                motif: $d['motif'],
                date: $d['date_mouvement'] ?? null,
                idTransfert: $idTransfert,
            );

            $this->enregistrer(
                articleId: $d['article_id'],
                entrepotId: $d['entrepot_destination_id'],
                delta: $quantite,
                type: 'transfert_entree',
                motif: $d['motif'],
                date: $d['date_mouvement'] ?? null,
                idTransfert: $idTransfert,
            );

            return $idTransfert;
        });
    }

    /** À appeler UNIQUEMENT dans une transaction. */
    private function enregistrer(
        int $articleId,
        int $entrepotId,
        int $delta,
        string $type,
        string $motif,
        ?string $date = null,
        ?int $fournisseurId = null,
        ?string $idTransfert = null,
        ?string $origineType = null,
        ?int $origineId = null,
    ): MouvementStock {
        $stock = $this->stockVerrouille($articleId, $entrepotId);
        $nouvelle = (int) $stock->quantite + $delta;

        if ($nouvelle < 0) {
            throw ValidationException::withMessages([
                'quantite' => "Stock insuffisant : {$stock->quantite} disponible(s), opération de " . abs($delta) . '.',
            ]);
        }

        $stock->update(['quantite' => $nouvelle]);

        $mouvement = MouvementStock::create([
            'stock_article_id' => $stock->id,
            'user_id' => Auth::id(),
            'fournisseur_id' => $fournisseurId,
            'type' => $type,
            'quantite' => $delta,
            'motif' => $motif,
            'date_mouvement' => $date ? Carbon::parse($date) : now(),
            'id_transfert' => $idTransfert,
            'origine_type' => $origineType,
            'origine_id' => $origineId,
        ]);

        $reference = Article::whereKey($articleId)->value('reference');
        Journal::log("mouvement_{$type}", 'mouvement', $mouvement->id, "{$delta} x {$reference} (dépôt #{$entrepotId}) : {$motif}");

        return $mouvement;
    }

    /** Crée la ligne de stock si elle n'existe pas, puis la verrouille jusqu'à la fin de la transaction. */
    private function stockVerrouille(int $articleId, int $entrepotId): StockArticle
    {
        DB::table('stock_articles')->insertOrIgnore([
            'article_id' => $articleId,
            'entrepot_id' => $entrepotId,
            'quantite' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return StockArticle::where('article_id', $articleId)
            ->where('entrepot_id', $entrepotId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}