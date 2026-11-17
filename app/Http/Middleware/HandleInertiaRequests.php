<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
    'user' => $request->user() ? [
        'id' => $request->user()->id,
        'name' => $request->user()->name,
        'role' => $request->user()->role?->code,
    ] : null,
],
'flash' => [
    'success' => fn () => $request->session()->get('success'),
],
'alertes' => fn () => $request->user()?->hasRole('admin', 'gestionnaire', 'controle')
    ? \App\Models\Article::where('actif', true)->sousSeuil()->count()
    : 0,

'notifications' => fn () => $request->user() ? [
    'non_lues' => $request->user()->unreadNotifications()->count(),
    'recentes' => $request->user()->notifications()->latest()->take(8)->get()->map(fn ($n) => [
        'id' => $n->id,
        'titre' => $n->data['titre'] ?? '',
        'message' => $n->data['message'] ?? '',
        'lu' => $n->read_at !== null,
        'date' => $n->created_at->diffForHumans(),
    ]),
] : null,
        ];

    }
}
