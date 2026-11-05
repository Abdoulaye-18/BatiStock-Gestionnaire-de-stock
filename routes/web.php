<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\EntrepotController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\StockController;

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
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('/categories', [CategorieController::class, 'index'])->name('categories.index');
    Route::get('/entrepots', [EntrepotController::class, 'index'])->name('entrepots.index');
});

    // Écriture sur le stock : gestionnaire, admin
    Route::middleware('role:admin,gestionnaire')->group(function () {
    Route::resource('articles', ArticleController::class)->only(['store', 'update']);
    Route::patch('/articles/{article}/statut', [ArticleController::class, 'toggle'])->name('articles.toggle');

    Route::resource('categories', CategorieController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters(['categories' => 'categorie']);

    Route::resource('entrepots', EntrepotController::class)->only(['store', 'update', 'destroy']);
    Route::resource('fournisseurs', FournisseurController::class)->only(['index', 'store', 'update', 'destroy']);
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
