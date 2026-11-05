<?php

namespace App\Services;

use App\Models\Categorie;
use App\Support\Journal;
use Illuminate\Validation\ValidationException;

class CategorieService
{
    public function creer(array $data): Categorie
    {
        $c = Categorie::create($data);
        Journal::log('categorie_creee', 'categorie', $c->id, $c->nom);

        return $c;
    }

    public function modifier(Categorie $c, array $data): void
    {
        $c->update($data);
        Journal::log('categorie_modifiee', 'categorie', $c->id, $c->nom);
    }

    public function supprimer(Categorie $c): void
    {
        if ($c->articles()->exists()) {
            throw ValidationException::withMessages([
                'suppression' => 'Suppression impossible : des articles utilisent cette catégorie.',
            ]);
        }

        Journal::log('categorie_supprimee', 'categorie', $c->id, $c->nom);
        $c->delete();
    }
}