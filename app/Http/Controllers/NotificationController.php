<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Marque la notification comme lue et ouvre la page concernée. */
    public function lire(Request $request, string $id)
    {
        // Recherche limitée aux notifications de l'utilisateur connecté (sinon 404)
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $lien = $notification->data['lien'] ?? null;

        return redirect(is_string($lien) && str_starts_with($lien, '/') ? $lien : route('dashboard'));
    }

    public function toutLire(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}