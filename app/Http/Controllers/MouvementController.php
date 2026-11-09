<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustementRequest;
use App\Http\Requests\EntreeRequest;
use App\Http\Requests\SortieRequest;
use App\Http\Requests\TransfertRequest;
use App\Models\Article;
use App\Models\Entrepot;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\StockArticle;
use App\Services\MouvementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Livraison;

class MouvementController extends Controller
{
    public function __construct(private MouvementService $service) {}

    public function index(Request $request)
    {
        $filtres = $request->only('q', 'type', 'entrepot_id', 'date_debut', 'date_fin');

        $mouvements = MouvementStock::query()
            ->with(['stock.article.unite', 'stock.entrepot', 'user:id,name', 'fournisseur:id,nom'])
            ->when($filtres['q'] ?? null, fn ($q, $v) => $q->whereHas('stock.article', fn ($a) => $a
                ->where('nom', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%")))
            ->when($filtres['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filtres['entrepot_id'] ?? null, fn ($q, $v) => $q->whereHas('stock', fn ($s) => $s->where('entrepot_id', $v)))
            ->when($filtres['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_mouvement', '>=', $v))
            ->when($filtres['date_fin'] ?? null, fn ($q, $v) => $q->whereDate('date_mouvement', '<=', $v))
            ->orderByDesc('date_mouvement')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (MouvementStock $m) => [
                'id' => $m->id,
                'date' => $m->date_mouvement->format('d/m/Y H:i'),
                'type' => $m->type,
                'reference' => $m->stock->article->reference,
                'article' => $m->stock->article->nom,
                'unite' => $m->stock->article->unite->code,
                'entrepot' => $m->stock->entrepot->nom,
                'quantite' => $m->quantite,
                'motif' => $m->motif,
                'utilisateur' => $m->user->name,
                'fournisseur' => $m->fournisseur?->nom,
                'transfert' => $m->id_transfert ? substr($m->id_transfert, 0, 8) : null,
                'origine' => $m->origine_type === 'livraison'
                    ? Livraison::whereKey($m->origine_id)->value('numero')
                    : null,
            ]);

        return Inertia::render('Mouvements/Index', [
            'mouvements' => $mouvements,
            'filtres' => $filtres,
            'articles' => Article::where('actif', true)->with('unite:id,code')->orderBy('nom')->get()
                ->map(fn ($a) => ['id' => $a->id, 'label' => "{$a->reference} - {$a->nom}", 'unite' => $a->unite->code]),
            'entrepots' => Entrepot::orderBy('nom')->get(['id', 'nom']),
            'fournisseurs' => Fournisseur::orderBy('nom')->get(['id', 'nom']),
            'stocks' => StockArticle::get(['article_id', 'entrepot_id', 'quantite']),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function entree(EntreeRequest $request)
    {
        $this->service->entree($request->validated());

        return back()->with('success', 'Entrée enregistrée.');
    }

    public function sortie(SortieRequest $request)
    {
        $this->service->sortie($request->validated());

        return back()->with('success', 'Sortie enregistrée.');
    }

    public function ajustement(AjustementRequest $request)
    {
        $this->service->ajustement($request->validated());

        return back()->with('success', 'Ajustement enregistré.');
    }

    public function transfert(TransfertRequest $request)
    {
        $this->service->transfert($request->validated());

        return back()->with('success', 'Transfert effectué.');
    }
}