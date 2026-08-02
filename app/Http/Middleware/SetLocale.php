<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Applique la langue choisie dans le profil de l'utilisateur connecté. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Compte connecté : préférence du profil. Visiteur : choix en session.
        $locale = $request->user()->locale ?? $request->session()->get('locale');

        if (is_string($locale) && in_array($locale, ['fr', 'en'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
