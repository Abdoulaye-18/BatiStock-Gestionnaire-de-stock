<?php

namespace App\Http\Controllers;

use App\Http\Requests\FournisseurRequest;
use App\Models\Fournisseur;
use App\Services\FournisseurService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FournisseurController extends Controller
{
    public function __construct(private FournisseurService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Fournisseurs/Index', [
            'fournisseurs' => Fournisseur::orderBy('nom')->get(['id', 'nom', 'telephone', 'email', 'adresse']),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(FournisseurRequest $request)
    {
        $this->service->creer($request->validated());

        return back()->with('success', 'Fournisseur créé.');
    }

    public function update(FournisseurRequest $request, Fournisseur $fournisseur)
    {
        $this->service->modifier($fournisseur, $request->validated());

        return back()->with('success', 'Fournisseur modifié.');
    }

    public function destroy(Fournisseur $fournisseur)
    {
        $this->service->supprimer($fournisseur);

        return back()->with('success', 'Fournisseur supprimé.');
    }
}