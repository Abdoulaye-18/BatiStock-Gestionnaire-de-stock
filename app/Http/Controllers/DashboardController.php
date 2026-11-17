<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CommandeClient;
use App\Models\DemandeChantier;

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
            'aTraiter' => $this->aTraiter($user),
        ]);
    }

    /** Travail en attente selon le rôle, calculé à partir de l'état des données. */
private function aTraiter(User $user): array
{
    $items = [];

    if ($user->hasRole('admin', 'gestionnaire')) {
        if (($n = DemandeChantier::where('statut', 'approuvee')->count()) > 0) {
            $items[] = [
                'label' => "{$n} demande(s) approuvée(s) à préparer",
                'href' => route('demandes.index', ['statut' => 'approuvee']),
                'couleur' => 'blue',
            ];
        }

        if (($n = CommandeClient::where('statut', 'confirmee')->count()) > 0) {
            $items[] = [
                'label' => "{$n} commande(s) confirmée(s) à préparer",
                'href' => route('commandes.index', ['statut' => 'confirmee']),
                'couleur' => 'blue',
            ];
        }
    }

    if ($user->hasRole('admin', 'controle')) {
        if (($n = DemandeChantier::where('statut', 'en_attente')->count()) > 0) {
            $items[] = [
                'label' => "{$n} demande(s) en attente de validation",
                'href' => route('demandes.index', ['statut' => 'en_attente']),
                'couleur' => 'orange',
            ];
        }
    }

    return $items;
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