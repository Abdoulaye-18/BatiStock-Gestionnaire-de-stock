<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntrepotRequest;
use App\Models\Entrepot;
use App\Services\EntrepotService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntrepotController extends Controller
{
    public function __construct(private EntrepotService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Entrepots/Index', [
            'entrepots' => Entrepot::orderBy('nom')->get(['id', 'code', 'nom', 'localisation']),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(EntrepotRequest $request)
    {
        $this->service->creer($request->validated());

        return back()->with('success', 'Dépôt créé.');
    }

    public function update(EntrepotRequest $request, Entrepot $entrepot)
    {
        $this->service->modifier($entrepot, $request->validated());

        return back()->with('success', 'Dépôt modifié.');
    }

    public function destroy(Entrepot $entrepot)
    {
        $this->service->supprimer($entrepot);

        return back()->with('success', 'Dépôt supprimé.');
    }
}