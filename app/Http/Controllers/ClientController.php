<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function __construct(private ClientService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Clients/Index', [
            'clients' => Client::orderBy('nom')->get(['id', 'nom', 'telephone', 'email', 'adresse_livraison']),
            'peutModifier' => $request->user()->hasRole('admin', 'gestionnaire'),
        ]);
    }

    public function store(ClientRequest $request)
    {
        $this->service->creer($request->validated());

        return back()->with('success', 'Client créé.');
    }

    public function update(ClientRequest $request, Client $client)
    {
        $this->service->modifier($client, $request->validated());

        return back()->with('success', 'Client modifié.');
    }

    public function destroy(Client $client)
    {
        $this->service->supprimer($client);

        return back()->with('success', 'Client supprimé.');
    }
}