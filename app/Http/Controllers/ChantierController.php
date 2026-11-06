<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChantierRequest;
use App\Models\Chantier;
use App\Models\User;
use App\Services\ChantierService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChantierController extends Controller
{
    public function __construct(private ChantierService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $etats = ['actif' => 'Actif', 'termine' => 'Terminé'];

        $chantiers = Chantier::with('responsable:id,name')
            ->when($user->hasRole('chantier'), fn ($q) => $q->where('responsable_id', $user->id))
            ->orderBy('nom')
            ->get()
            ->map(fn (Chantier $c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'localisation' => $c->localisation,
                'etat' => $c->etat,
                'etat_label' => $etats[$c->etat] ?? $c->etat,
                'responsable_id' => $c->responsable_id,
                'responsable_nom' => $c->responsable?->name,
            ]);

        return Inertia::render('Chantiers/Index', [
            'chantiers' => $chantiers,
            'responsables' => User::where('actif', true)
                ->whereHas('role', fn ($q) => $q->where('code', 'chantier'))
                ->orderBy('name')->get(['id', 'name']),
            'avecResponsable' => ! $user->hasRole('chantier'),
            'peutCreer' => $user->hasRole('admin', 'gestionnaire', 'chantier'),
            'peutSupprimer' => $user->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(ChantierRequest $request)
    {
        $this->service->creer($request->validated(), $request->user());

        return back()->with('success', 'Chantier créé.');
    }

    public function update(ChantierRequest $request, Chantier $chantier)
    {
        $this->service->modifier($chantier, $request->validated(), $request->user());

        return back()->with('success', 'Chantier modifié.');
    }

    public function destroy(Chantier $chantier)
    {
        $this->service->supprimer($chantier);

        return back()->with('success', 'Chantier supprimé.');
    }
}