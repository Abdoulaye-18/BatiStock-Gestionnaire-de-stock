<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified', 'role:admin,gestionnaire,chantier,controle'])->group(function () {

    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Lecture : gestionnaire, contrôle, admin
    Route::middleware('role:admin,gestionnaire,controle')->group(function () {
        // Route::get('/articles', ...)
        // Route::get('/mouvements', ...)
    });

    // Écriture sur le stock : gestionnaire, admin
    Route::middleware('role:admin,gestionnaire')->group(function () {
        // Route::post('/articles', ...)
        // Route::post('/mouvements/sortie', ...)
    });

    // Administration
    Route::middleware('role:admin')->group(function () {
        // Route::resource('/utilisateurs', ...)
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
