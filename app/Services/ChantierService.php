<?php

namespace App\Services;

use App\Models\Chantier;
use App\Models\User;
use App\Support\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChantierService
{
    public function creer(array $data, User $user): Chantier
    {
        // Un responsable de chantier est toujours le responsable de ce qu'il crée
        if ($user->hasRole('chantier')) {
            $data['responsable_id'] = $user->id;
        }

        $c = Chantier::create($data);
        Journal::log('chantier_cree', 'chantier', $c->id, $c->nom);

        return $c;
    }

    public function modifier(Chantier $c, array $data, User $user): void
    {
        // Il ne peut pas se désigner un autre responsable ni en changer
        if ($user->hasRole('chantier')) {
            unset($data['responsable_id']);
        }

        $c->update($data);
        Journal::log('chantier_modifie', 'chantier', $c->id, $c->nom);
    }

    public function supprimer(Chantier $c): void
    {
        if (DB::table('demandes_chantier')->where('chantier_id', $c->id)->exists()) {
            throw ValidationException::withMessages([
                'suppression' => 'Suppression impossible : ce chantier a des demandes. Passez-le à « Terminé ».',
            ]);
        }

        Journal::log('chantier_supprime', 'chantier', $c->id, $c->nom);
        $c->delete();
    }
}