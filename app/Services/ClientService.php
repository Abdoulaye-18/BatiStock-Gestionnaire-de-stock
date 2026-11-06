<?php

namespace App\Services;

use App\Models\Client;
use App\Support\Journal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientService
{
    public function creer(array $data): Client
    {
        $c = Client::create($data);
        Journal::log('client_cree', 'client', $c->id, $c->nom);

        return $c;
    }

    public function modifier(Client $c, array $data): void
    {
        $c->update($data);
        Journal::log('client_modifie', 'client', $c->id, $c->nom);
    }

    public function supprimer(Client $c): void
    {
        if (DB::table('commandes_client')->where('client_id', $c->id)->exists()) {
            throw ValidationException::withMessages([
                'suppression' => 'Suppression impossible : ce client a des commandes.',
            ]);
        }

        Journal::log('client_supprime', 'client', $c->id, $c->nom);
        $c->delete();
    }
}