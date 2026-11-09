<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Chantier;
use App\Models\CommandeClient;
use App\Models\DemandeChantier;
use App\Models\Livraison;
use App\Models\MouvementStock;
use App\Models\StockArticle;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const PALETTE = ['#3b82f6', '#10b981', '#d9b44a', '#ec4899', '#8b5cf6', '#f43f5e', '#f97316', '#14b8a6'];

    /** Vue complète : admin, gestionnaire, responsable de contrôle. */
    public function global(): array
    {
        $stockTotal = (int) StockArticle::sum('quantite');
        $sousSeuil = $this->articlesSousSeuil();

        $livraisonsActives = Livraison::whereIn('statut', ['planifiee', 'en_cours']);
        $retards = (clone $livraisonsActives)->whereDate('date_prevue', '<', today())->count();
        $nouveaux = Article::where('actif', true)->where('created_at', '>=', now()->startOfMonth())->count();

        return [
            'kpis' => [
                [
                    'titre' => 'Articles', 'valeur' => Article::where('actif', true)->count(),
                    'sous_titre' => $nouveaux > 0 ? "+{$nouveaux} ce mois" : 'au catalogue',
                    'icone' => 'package', 'couleur' => 'blue', 'href' => route('articles.index'),
                ],
                [
                    'titre' => 'Stock total', 'valeur' => $stockTotal, 'sous_titre' => 'unités',
                    'icone' => 'boxes', 'couleur' => 'green', 'href' => route('stocks.index'),
                ],
                [
                    'titre' => 'Commandes en cours',
                    'valeur' => CommandeClient::whereIn('statut', ['confirmee', 'en_preparation', 'partiellement_livree'])->count(),
                    'sous_titre' => 'à traiter', 'icone' => 'cart', 'couleur' => 'blue', 'href' => route('commandes.index'),
                ],
                [
                    'titre' => 'Demandes en attente',
                    'valeur' => DemandeChantier::where('statut', 'en_attente')->count(),
                    'sous_titre' => 'à valider', 'icone' => 'file', 'couleur' => 'purple',
                    'href' => route('demandes.index', ['statut' => 'en_attente']),
                ],
                [
                    'titre' => 'Livraisons en cours', 'valeur' => (clone $livraisonsActives)->count(),
                    'sous_titre' => $retards > 0 ? "{$retards} en retard" : 'à venir',
                    'icone' => 'truck', 'couleur' => $retards > 0 ? 'orange' : 'blue', 'href' => route('livraisons.index'),
                ],
                [
                    'titre' => 'Articles en stock faible', 'valeur' => $sousSeuil->count(), 'sous_titre' => 'sous le seuil',
                    'icone' => 'alert', 'couleur' => 'red', 'href' => route('articles.index', ['statut' => 'faible']),
                ],
            ],
            'evolution' => $this->evolution($stockTotal),
            'mouvements' => $this->derniersMouvements(),
            'categories' => $this->repartitionParCategorie(),
            'alertes' => $sousSeuil->take(5)->map(function (Article $a) {
                $stock = (int) $a->stock_total;

                return [
                    'article' => $a->nom,
                    'reference' => $a->reference,
                    'categorie' => $a->categorie->nom,
                    'stock' => $stock,
                    'unite' => $a->unite->code,
                    'seuil' => $a->seuil_alerte,
                    'statut' => $stock <= 0 ? 'rupture' : 'faible',
                ];
            })->values(),
            'livraisons' => $this->prochainesLivraisons(),
        ];
    }

    /** Vue réduite du responsable de chantier : uniquement ses chantiers et ses demandes. */
    public function responsable(User $user): array
    {
        $mes = fn () => DemandeChantier::where('demandeur_id', $user->id);

        return [
            'kpis' => [
                [
                    'titre' => 'Mes chantiers actifs',
                    'valeur' => Chantier::where('responsable_id', $user->id)->where('etat', 'actif')->count(),
                    'sous_titre' => 'en cours', 'icone' => 'hardhat', 'couleur' => 'blue', 'href' => route('chantiers.index'),
                ],
                [
                    'titre' => 'Demandes en attente', 'valeur' => $mes()->where('statut', 'en_attente')->count(),
                    'sous_titre' => 'à valider', 'icone' => 'file', 'couleur' => 'orange',
                    'href' => route('demandes.index', ['statut' => 'en_attente']),
                ],
                [
                    'titre' => 'Demandes en cours',
                    'valeur' => $mes()->whereIn('statut', ['approuvee', 'en_preparation', 'partiellement_satisfaite'])->count(),
                    'sous_titre' => 'en traitement', 'icone' => 'truck', 'couleur' => 'blue', 'href' => route('demandes.index'),
                ],
                [
                    'titre' => 'Demandes satisfaites', 'valeur' => $mes()->where('statut', 'satisfaite')->count(),
                    'sous_titre' => 'livrées', 'icone' => 'check', 'couleur' => 'green',
                    'href' => route('demandes.index', ['statut' => 'satisfaite']),
                ],
            ],
            'demandes' => $mes()->with('chantier:id,nom')->orderByDesc('id')->take(5)->get()
                ->map(fn (DemandeChantier $d) => [
                    'numero' => $d->numero,
                    'chantier' => $d->chantier->nom,
                    'date' => $d->created_at->format('d/m/Y'),
                    'statut' => $d->statut,
                    'statut_label' => DemandeChantier::STATUTS[$d->statut] ?? $d->statut,
                ])->values(),
        ];
    }

    /** Articles actifs dont le stock total est sous le seuil, les plus critiques d'abord. */
    private function articlesSousSeuil()
    {
        return Article::where('actif', true)
            ->sousSeuil()
            ->with(['categorie:id,nom', 'unite:id,code'])
            ->withSum('stocks as stock_total', 'quantite')
            ->get()
            ->sortBy(fn (Article $a) => $a->seuil_alerte > 0 ? ((int) $a->stock_total / $a->seuil_alerte) : 1)
            ->values();
    }

    /**
     * Stock total à la fin de chacun des 7 derniers jours.
     * On remonte le temps : stock d'un jour = stock actuel - mouvements enregistrés après ce jour.
     */
    private function evolution(int $stockTotal): array
    {
        return collect(range(6, 0))->map(function (int $i) use ($stockTotal) {
            $fin = now()->subDays($i)->endOfDay();
            $apres = (int) MouvementStock::where('date_mouvement', '>', $fin)->sum('quantite');

            return ['date' => $fin->format('d/m'), 'total' => $stockTotal - $apres];
        })->all();
    }

    private function derniersMouvements(): array
    {
        return MouvementStock::with(['stock.article.unite', 'user:id,name'])
            ->orderByDesc('date_mouvement')->orderByDesc('id')
            ->take(5)->get()
            ->map(fn (MouvementStock $m) => [
                'type' => $m->type,
                'article' => $m->stock->article->nom,
                'quantite' => $m->quantite,
                'unite' => $m->stock->article->unite->code,
                'date' => $m->date_mouvement->format('d/m/Y H:i'),
                'utilisateur' => $m->user->name,
            ])->all();
    }

    private function repartitionParCategorie(): array
    {
        $rows = DB::table('stock_articles')
            ->join('articles', 'articles.id', '=', 'stock_articles.article_id')
            ->join('categories', 'categories.id', '=', 'articles.categorie_id')
            ->selectRaw('categories.nom as nom, SUM(stock_articles.quantite) as total')
            ->groupBy('categories.id', 'categories.nom')
            ->havingRaw('SUM(stock_articles.quantite) > 0')
            ->orderByDesc('total')
            ->get();

        $somme = (int) $rows->sum('total');

        return $rows->values()->map(fn ($r, $i) => [
            'label' => $r->nom,
            'value' => (int) $r->total,
            'pct' => $somme > 0 ? (int) round($r->total * 100 / $somme) : 0,
            'color' => self::PALETTE[$i % count(self::PALETTE)],
        ])->all();
    }

    private function prochainesLivraisons(): array
    {
        return Livraison::with(['commande.client:id,nom', 'demande.chantier:id,nom'])
            ->whereIn('statut', ['planifiee', 'en_cours'])
            ->orderBy('date_prevue')->orderBy('id')
            ->take(5)->get()
            ->map(fn (Livraison $l) => [
                'numero' => $l->numero,
                'destinataire' => $l->commande?->client->nom ?? $l->demande?->chantier->nom,
                'date' => $l->date_prevue->format('d/m/Y'),
                'statut' => $l->statut,
                'en_retard' => $l->date_prevue->lt(today()),
            ])->all();
    }
}