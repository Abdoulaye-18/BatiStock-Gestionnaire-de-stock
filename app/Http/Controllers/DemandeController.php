<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeRequest;
use App\Http\Requests\RefusDemandeRequest;
use App\Models\Article;
use App\Models\Chantier;
use App\Models\DemandeChantier;
use App\Models\StockArticle;
use App\Services\DemandeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DemandeController extends Controller
{
    public function __construct(private DemandeService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $statut = $request->input('statut');
        $estResponsable = $user->hasRole('chantier');

        // Le stock n'est visible que pour les rôles qui ont accès au module Stocks
        $stockTotal = $estResponsable
            ? collect()
            : StockArticle::selectRaw('article_id, SUM(quantite) as total')->groupBy('article_id')->pluck('total', 'article_id');

        $demandes = DemandeChantier::with(['chantier:id,nom', 'demandeur:id,name', 'validateur:id,name', 'lignes.article.unite'])
            ->when($estResponsable, fn ($q) => $q->where('demandeur_id', $user->id))
            ->when($statut, fn ($q, $v) => $q->where('statut', $v))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (DemandeChantier $d) => [
                'id' => $d->id,
                'numero' => $d->numero,
                'chantier' => $d->chantier->nom,
                'demandeur' => $d->demandeur->name,
                'demandeur_id' => $d->demandeur_id,
                'validateur' => $d->validateur?->name,
                'statut' => $d->statut,
                'motif' => $d->motif,
                'motif_refus' => $d->motif_refus,
                'date_souhaitee' => $d->date_souhaitee?->format('d/m/Y'),
                'creee_le' => $d->created_at->format('d/m/Y'),
                'lignes' => $d->lignes->map(fn ($l) => [
                    'id' => $l->id,
                    'article' => $l->article->nom,
                    'reference' => $l->article->reference,
                    'unite' => $l->article->unite->code,
                    'quantite' => $l->quantite,
                    'quantite_servie' => $l->quantite_servie,
                    'disponible' => $estResponsable ? null : (int) ($stockTotal[$l->article_id] ?? 0),
                ])->values(),
            ]);

        return Inertia::render('Demandes/Index', [
            'demandes' => $demandes,
            'statut' => $statut,
            'statuts' => DemandeChantier::STATUTS,
            'chantiers' => Chantier::where('etat', 'actif')
                ->when($estResponsable, fn ($q) => $q->where('responsable_id', $user->id))
                ->orderBy('nom')->get(['id', 'nom']),
            'articles' => Article::where('actif', true)->with('unite:id,code')->orderBy('nom')->get()
                ->map(fn ($a) => ['id' => $a->id, 'label' => "{$a->reference} - {$a->nom}", 'unite' => $a->unite->code]),
            'moiId' => $user->id,
            'peutCreer' => $user->hasRole('admin', 'chantier'),
            'peutDecider' => $user->hasRole('admin', 'controle'),
            'peutPreparer' => $user->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(DemandeRequest $request)
    {
        $this->service->creer($request->validated(), $request->user());

        return back()->with('success', 'Demande soumise.');
    }

    public function approuver(Request $request, DemandeChantier $demande)
    {
        $this->service->approuver($demande, $request->user());

        return back()->with('success', 'Demande approuvée.');
    }

    public function refuser(RefusDemandeRequest $request, DemandeChantier $demande)
    {
        $this->service->refuser($demande, $request->user(), $request->validated()['motif_refus']);

        return back()->with('success', 'Demande refusée.');
    }

    public function preparer(DemandeChantier $demande)
    {
        $this->service->preparer($demande);

        return back()->with('success', 'Demande passée en préparation.');
    }
}