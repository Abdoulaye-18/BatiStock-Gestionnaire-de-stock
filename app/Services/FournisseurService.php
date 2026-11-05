<?php

namespace App\Services;

use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Support\Journal;
use Illuminate\Validation\ValidationException;

class FournisseurService
{
    public function creer(array $data): Fournisseur
    {
        $f = Fournisseur::create($data);
        Journal::log('fournisseur_cree', 'fournisseur', $f->id, $f->nom);

        return $f;
    }

    public function modifier(Fournisseur $f, array $data): void
    {
        $f->update($data);
        Journal::log('fournisseur_modifie', 'fournisseur', $f->id, $f->nom);
    }

    public function supprimer(Fournisseur $f): void
    {
        if (MouvementStock::where('fournisseur_id', $f->id)->exists()) {
            throw ValidationException::withMessages([
                'suppression' => 'Suppression impossible : ce fournisseur est lié à des entrées de stock.',
            ]);
        }

        Journal::log('fournisseur_supprime', 'fournisseur', $f->id, $f->nom);
        $f->delete();
    }
}