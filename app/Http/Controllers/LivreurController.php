<?php

namespace App\Http\Controllers;

use App\Http\Requests\LivreurRequest;
use App\Models\Livreur;
use App\Services\LivreurService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LivreurController extends Controller
{
    public function __construct(private LivreurService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Livreurs/Index', [
            'livreurs' => Livreur::orderBy('nom')->get()->map(fn (Livreur $l) => [
                'id' => $l->id,
                'nom' => $l->nom,
                'telephone' => $l->telephone,
                'disponible' => (int) $l->disponible,
                'disponible_label' => $l->disponible ? 'Disponible' : 'Indisponible',
            ]),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(LivreurRequest $request)
    {
        $this->service->creer($request->validated());

        return back()->with('success', 'Livreur créé.');
    }

    public function update(LivreurRequest $request, Livreur $livreur)
    {
        $this->service->modifier($livreur, $request->validated());

        return back()->with('success', 'Livreur modifié.');
    }

    public function destroy(Livreur $livreur)
    {
        $this->service->supprimer($livreur);

        return back()->with('success', 'Livreur supprimé.');
    }
}