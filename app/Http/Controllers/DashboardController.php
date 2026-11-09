<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $service)
    {
        $user = $request->user();
        $estResponsable = $user->hasRole('chantier');

        return Inertia::render('Dashboard', [
            'vue' => $estResponsable ? 'chantier' : 'global',
            'donnees' => $estResponsable ? $service->responsable($user) : $service->global(),
            'actions' => $this->actions($user),
        ]);
    }

    /** Actions rapides, uniquement celles que le rôle a le droit d'effectuer. */
    private function actions(User $user): array
    {
        $actions = [];

        if ($user->hasRole('admin', 'gestionnaire')) {
            $actions[] = [
                'label' => 'Nouvelle entrée', 'description' => 'Ajouter des matériaux au stock',
                'href' => route('mouvements.index', ['ouvrir' => 'entree']), 'icone' => 'entree', 'couleur' => 'green',
            ];
            $actions[] = [
                'label' => 'Nouvelle sortie', 'description' => 'Retirer des matériaux du stock',
                'href' => route('mouvements.index', ['ouvrir' => 'sortie']), 'icone' => 'sortie', 'couleur' => 'red',
            ];
            $actions[] = [
                'label' => 'Nouvelle livraison', 'description' => 'Planifier une livraison',
                'href' => route('livraisons.index', ['ouvrir' => 'nouvelle']), 'icone' => 'livraison', 'couleur' => 'blue',
            ];
        }

        if ($user->hasRole('admin', 'chantier')) {
            $actions[] = [
                'label' => 'Nouvelle demande', 'description' => 'Demande d\'approvisionnement chantier',
                'href' => route('demandes.index', ['ouvrir' => 'nouvelle']), 'icone' => 'demande', 'couleur' => 'purple',
            ];
        }

        return $actions;
    }
}