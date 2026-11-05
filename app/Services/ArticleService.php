<?php

namespace App\Services;

use App\Models\Article;
use App\Support\Journal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function liste(array $filtres): LengthAwarePaginator
    {
        return Article::query()
            ->with(['categorie:id,nom', 'unite:id,code'])
            ->withSum('stocks as stock_total', 'quantite')
            ->when($filtres['q'] ?? null, fn ($q, $v) => $q->where(
                fn ($w) => $w->where('nom', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%")
            ))
            ->when($filtres['categorie_id'] ?? null, fn ($q, $v) => $q->where('categorie_id', $v))
            ->when(($filtres['statut'] ?? null) === 'faible', fn ($q) => $q->sousSeuil())
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString()
            ->through(function (Article $a) {
                $total = (int) $a->stock_total;

                return [
                    'id' => $a->id,
                    'reference' => $a->reference,
                    'nom' => $a->nom,
                    'categorie_id' => $a->categorie_id,
                    'categorie' => $a->categorie->nom,
                    'unite_id' => $a->unite_id,
                    'unite' => $a->unite->code,
                    'prix_vente' => $a->prix_vente,
                    'seuil_alerte' => $a->seuil_alerte,
                    'actif' => $a->actif,
                    'stock_total' => $total,
                    'statut' => Article::statutStock($total, $a->seuil_alerte),
                ];
            });
    }

    public function creer(array $data): Article
    {
        return DB::transaction(function () use ($data) {
            $article = Article::create($data + ['actif' => true]);
            Journal::log('article_cree', 'article', $article->id, "{$article->reference} - {$article->nom}");

            return $article;
        });
    }

    public function modifier(Article $article, array $data): void
    {
        DB::transaction(function () use ($article, $data) {
            $article->update($data);
            Journal::log('article_modifie', 'article', $article->id, $article->reference);
        });
    }

    /** Un article n'est jamais supprimé : il est désactivé (traçabilité). */
    public function basculerStatut(Article $article): void
    {
        $article->update(['actif' => ! $article->actif]);
        Journal::log($article->actif ? 'article_active' : 'article_desactive', 'article', $article->id, $article->reference);
    }
}