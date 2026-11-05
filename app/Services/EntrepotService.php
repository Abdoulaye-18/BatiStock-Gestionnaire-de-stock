<?php

namespace App\Services;

use App\Models\Entrepot;
use App\Support\Journal;
use Illuminate\Validation\ValidationException;

class EntrepotService
{
    public function creer(array $data): Entrepot
    {
        $e = Entrepot::create($data);
        Journal::log('depot_cree', 'entrepot', $e->id, $e->nom);

        return $e;
    }

    public function modifier(Entrepot $e, array $data): void
    {
        $e->update($data);
        Journal::log('depot_modifie', 'entrepot', $e->id, $e->nom);
    }

    public function supprimer(Entrepot $e): void
    {
        if ($e->stocks()->exists()) {
            throw ValidationException::withMessages([
                'suppression' => 'Suppression impossible : ce dépôt possède un historique de stock.',
            ]);
        }

        Journal::log('depot_supprime', 'entrepot', $e->id, $e->nom);
        $e->delete();
    }
}