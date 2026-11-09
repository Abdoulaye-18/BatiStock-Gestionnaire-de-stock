<?php

namespace App\Services;

use App\Models\Livreur;
use App\Support\Journal;
use Illuminate\Validation\ValidationException;

class LivreurService
{
    public function creer(array $data): Livreur
    {
        $l = Livreur::create($data);
        Journal::log('livreur_cree', 'livreur', $l->id, $l->nom);

        return $l;
    }

    public function modifier(Livreur $l, array $data): void
    {
        $l->update($data);
        Journal::log('livreur_modifie', 'livreur', $l->id, $l->nom);
    }

    public function supprimer(Livreur $l): void
    {
        if ($l->livraisons()->exists()) {
            throw ValidationException::withMessages([
                'suppression' => 'Suppression impossible : ce livreur est associé à des livraisons.',
            ]);
        }

        Journal::log('livreur_supprime', 'livreur', $l->id, $l->nom);
        $l->delete();
    }
}
