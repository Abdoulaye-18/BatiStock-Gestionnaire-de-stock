<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnnulationCommandeRequest;
use App\Http\Requests\CommandeRequest;
use App\Models\Article;
use App\Models\Client;
use App\Models\CommandeClient;
use App\Models\StockArticle;
use App\Services\CommandeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommandeController extends Controller
{
    public function __construct(private CommandeService $service) {}

    public function index(Request $request)
    {
        $filtres = $request->only('q', 'statut');
        $stockTotal = StockArticle::selectRaw('article_id, SUM(quantite) as total')
            ->groupBy('article_id')->pluck('total', 'article_id');

        $commandes = CommandeClient::with(['client:id,nom', 'createur:id,name', 'lignes.article.unite'])
            ->when($filtres['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('numero', 'like', "%{$v}%")
                ->orWhereHas('client', fn ($c) => $c->where('nom', 'like', "%{$v}%"))))
            ->when($filtres['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (CommandeClient $c) => [
                'id' => $c->id,
                'numero' => $c->numero,
                'client' => $c->client->nom,
                'client_id' => $c->client_id,
                'adresse_destination' => $c->adresse_destination,
                'date' => $c->date_commande->format('d/m/Y'),
                'date_iso' => $c->date_commande->format('Y-m-d'),
                'statut' => $c->statut,
                'montant_total' => (float) $c->montant_total,
                'createur' => $c->createur->name,
                'lignes' => $c->lignes->map(fn ($l) => [
                    'id' => $l->id,
                    'article_id' => $l->article_id,
                    'article' => $l->article->nom,
                    'reference' => $l->article->reference,
                    'unite' => $l->article->unite->code,
                    'quantite' => $l->quantite,
                    'quantite_livree' => $l->quantite_livree,
                    'prix_unitaire' => (float) $l->prix_unitaire,
                    'disponible' => (int) ($stockTotal[$l->article_id] ?? 0),
                ])->values(),
            ]);

        return Inertia::render('Commandes/Index', [
            'commandes' => $commandes,
            'filtres' => $filtres,
            'statuts' => CommandeClient::STATUTS,
            'clients' => Client::orderBy('nom')->get(['id', 'nom', 'adresse_livraison']),
            'articles' => Article::where('actif', true)->with('unite:id,code')->orderBy('nom')->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'label' => "{$a->reference} - {$a->nom}",
                    'unite' => $a->unite->code,
                    'prix_vente' => $a->prix_vente !== null ? (float) $a->prix_vente : null,
                ]),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(CommandeRequest $request)
    {
        $this->service->creer($request->validated(), $request->user());

        return back()->with('success', 'Commande créée en brouillon.');
    }

    public function update(CommandeRequest $request, CommandeClient $commande)
    {
        $this->service->modifier($commande, $request->validated());

        return back()->with('success', 'Commande modifiée.');
    }

    public function confirmer(CommandeClient $commande)
    {
        $this->service->confirmer($commande);

        return back()->with('success', 'Commande confirmée.');
    }

    public function preparer(CommandeClient $commande)
    {
        $this->service->preparer($commande);

        return back()->with('success', 'Commande passée en préparation.');
    }

    public function annuler(AnnulationCommandeRequest $request, CommandeClient $commande)
    {
        $this->service->annuler($commande, $request->validated()['motif_annulation']);

        return back()->with('success', 'Commande annulée.');
    }
}