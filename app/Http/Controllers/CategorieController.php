<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategorieRequest;
use App\Models\Categorie;
use App\Services\CategorieService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategorieController extends Controller
{
    public function __construct(private CategorieService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Categories/Index', [
            'categories' => Categorie::orderBy('nom')->get(['id', 'code', 'nom']),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(CategorieRequest $request)
    {
        $this->service->creer($request->validated());

        return back()->with('success', 'Catégorie créée.');
    }

    public function update(CategorieRequest $request, Categorie $categorie)
    {
        $this->service->modifier($categorie, $request->validated());

        return back()->with('success', 'Catégorie modifiée.');
    }

    public function destroy(Categorie $categorie)
    {
        $this->service->supprimer($categorie);

        return back()->with('success', 'Catégorie supprimée.');
    }
}