<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmationLivraisonRequest;
use App\Http\Requests\LivraisonRequest;
use App\Models\Entrepot;
use App\Models\Livraison;
use App\Models\Livreur;
use App\Models\StockArticle;
use App\Services\LivraisonService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LivraisonController extends Controller
{
    public function __construct(private LivraisonService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $peutModifier = $user->hasRole('admin', 'gestionnaire');
        $filtres = $request->only('q', 'statut');

        $stocks = StockArticle::get(['article_id', 'entrepot_id', 'quantite']);
        $stockMap = $stocks->keyBy(fn ($s) => "{$s->article_id}-{$s->entrepot_id}");

        $livraisons = Livraison::with([
            'commande.client:id,nom', 'demande.chantier:id,nom', 'livreur:id,nom',
            'lignes.article.unite', 'lignes.entrepot:id,nom',
        ])
            ->when($filtres['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('numero', 'like', "%{$v}%")->orWhere('adresse_destination', 'like', "%{$v}%")))
            ->when($filtres['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Livraison $l) => [
                'id' => $l->id,
                'numero' => $l->numero,
                'origine' => $l->commande?->numero ?? $l->demande?->numero,
                'type' => $l->commande_id ? 'Commande' : 'Demande de chantier',
                'destinataire' => $l->commande?->client->nom ?? $l->demande?->chantier->nom,
                'adresse_destination' => $l->adresse_destination,
                'date_prevue' => $l->date_prevue->format('d/m/Y'),
                'date_effective' => $l->date_effective?->format('d/m/Y'),
                'statut' => $l->statut,
                'livreur' => $l->livreur?->nom,
                'observation' => $l->observation,
                'lignes' => $l->lignes->map(fn ($x) => [
                    'id' => $x->id,
                    'article' => $x->article->nom,
                    'reference' => $x->article->reference,
                    'unite' => $x->article->unite->code,
                    'entrepot' => $x->entrepot->nom,
                    'qte_prevue' => $x->qte_prevue,
                    'qte_livree' => $x->qte_livree,
                    'motif_ecart' => $x->motif_ecart,
                    'disponible' => (int) ($stockMap->get("{$x->article_id}-{$x->entrepot_id}")?->quantite ?? 0),
                ])->values(),
            ]);

        return Inertia::render('Livraisons/Index', [
            'livraisons' => $livraisons,
            'filtres' => $filtres,
            'statuts' => Livraison::STATUTS,
            'sources' => $peutModifier ? $this->service->sourcesEligibles() : [],
            'livreurs' => Livreur::where('disponible', true)->orderBy('nom')->get(['id', 'nom']),
            'entrepots' => Entrepot::orderBy('nom')->get(['id', 'nom']),
            'stocks' => $stocks,
            'peutModifier' => $peutModifier,
        ]);
    }

    public function store(LivraisonRequest $request)
    {
        $this->service->planifier($request->validated(), $request->user());

        return back()->with('success', 'Livraison planifiée.');
    }

    public function demarrer(Livraison $livraison)
    {
        $this->service->demarrer($livraison);

        return back()->with('success', 'Livraison démarrée.');
    }

    public function confirmer(ConfirmationLivraisonRequest $request, Livraison $livraison)
    {
        $this->service->confirmer($livraison, $request->validated());

        return back()->with('success', 'Remise confirmée, stock mis à jour.');
    }
}