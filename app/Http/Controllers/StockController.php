<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Entrepot;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');

        $articles = Article::where('actif', true)
            ->with(['stocks:id,article_id,entrepot_id,quantite', 'unite:id,code'])
            ->when($q, fn ($query, $v) => $query->where(
                fn ($w) => $w->where('nom', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%")
            ))
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Article $a) {
                $total = (int) $a->stocks->sum('quantite');

                return [
                    'id' => $a->id,
                    'reference' => $a->reference,
                    'nom' => $a->nom,
                    'unite' => $a->unite->code,
                    'seuil' => $a->seuil_alerte,
                    'total' => $total,
                    'par_depot' => $a->stocks->pluck('quantite', 'entrepot_id'),
                    'statut' => Article::statutStock($total, $a->seuil_alerte),
                ];
            });

        return Inertia::render('Stocks/Index', [
            'articles' => $articles,
            'entrepots' => Entrepot::orderBy('nom')->get(['id', 'nom']),
            'q' => $q,
        ]);
    }
}