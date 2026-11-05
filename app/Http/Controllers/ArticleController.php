<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\Unite;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function __construct(private ArticleService $service) {}

    public function index(Request $request)
    {
        $filtres = $request->only('q', 'categorie_id', 'statut');

        return Inertia::render('Articles/Index', [
            'articles' => $this->service->liste($filtres),
            'filtres' => $filtres,
            'categories' => Categorie::orderBy('nom')->get(['id', 'nom']),
            'unites' => Unite::orderBy('nom')->get(['id', 'code', 'nom']),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(ArticleRequest $request)
    {
        $this->service->creer($request->validated());

        return back()->with('success', 'Article créé.');
    }

    public function update(ArticleRequest $request, Article $article)
    {
        $this->service->modifier($article, $request->validated());

        return back()->with('success', 'Article modifié.');
    }

    public function toggle(Article $article)
    {
        $this->service->basculerStatut($article);

        return back()->with('success', 'Statut de l\'article mis à jour.');
    }
}