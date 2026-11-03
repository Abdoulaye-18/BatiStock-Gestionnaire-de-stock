<?php

namespace App\Support;

use App\Models\JournalActivite;
use Illuminate\Support\Facades\Auth;

class Journal
{
    public static function log(string $action, ?string $entite = null, ?int $entiteId = null, ?string $detail = null): void
    {
        JournalActivite::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entite' => $entite,
            'entite_id' => $entiteId,
            'detail' => $detail,
            'ip' => request()->ip(),
        ]);
    }
}