<?php

use App\Enums\Permission;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tableau-de-bord');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/tableau-de-bord', DashboardController::class)->name('dashboard');

    // Mon profil — uniquement la ligne de l'utilisateur connecté, jamais d'id.
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profil/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::delete('/profil/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
    Route::post('/profil/mot-de-passe', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profil/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');

    // Roadmap produit : contribution ouverte à tous les comptes ;
    // l'arbitrage est vérifié par le Gate roadmap.manage dans le contrôleur.
    Route::get('/roadmap', [App\Http\Controllers\RoadmapController::class, 'index'])->name('roadmap.index');
    Route::post('/roadmap', [App\Http\Controllers\RoadmapController::class, 'store'])->name('roadmap.store');
    Route::get('/roadmap/{feature}', [App\Http\Controllers\RoadmapController::class, 'show'])->name('roadmap.show');
    Route::put('/roadmap/{feature}', [App\Http\Controllers\RoadmapController::class, 'update'])->name('roadmap.update');
    Route::delete('/roadmap/{feature}', [App\Http\Controllers\RoadmapController::class, 'destroy'])->name('roadmap.destroy');
    Route::post('/roadmap/{feature}/vote', [App\Http\Controllers\RoadmapController::class, 'vote'])->name('roadmap.vote');
    Route::post('/roadmap/{feature}/commentaires', [App\Http\Controllers\RoadmapController::class, 'comment'])->name('roadmap.comment');

    // Administration des comptes (gate users.manage).
    Route::middleware('can:'.Permission::ManageUsers->value)->group(function () {
        Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
        Route::get('/utilisateurs/nouveau', [UserController::class, 'create'])->name('users.create');
        Route::post('/utilisateurs', [UserController::class, 'store'])->name('users.store');
        Route::get('/utilisateurs/{user}', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/utilisateurs/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/utilisateurs/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Réglages de l'application (gate settings.manage ; connecteurs vérifiés
    // en plus par le Gate connectors.manage dans le contrôleur).
    Route::middleware('can:'.Permission::ManageSettings->value)->group(function () {
        Route::get('/reglages', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/reglages/marque', [SettingsController::class, 'updateBranding'])->name('settings.branding');
        Route::delete('/reglages/marque/logo', [SettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
        Route::post('/reglages/connecteurs/smtp', [SettingsController::class, 'updateSmtp'])->name('settings.smtp');
        Route::post('/reglages/connecteurs/smtp/test', [SettingsController::class, 'testSmtp'])->name('settings.smtp.test');
    });
});
