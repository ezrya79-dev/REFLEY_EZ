<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pages éditoriales publiques (contenu géré via /contenu). */
class PublicPageController extends Controller
{
    public function home(Request $request): View|RedirectResponse
    {
        // Les comptes connectés vont à leur espace ; les visiteurs voient l'accueil.
        if ($request->user() !== null) {
            return redirect()->route('dashboard');
        }

        return view('pages.accueil');
    }

    public function about(): View
    {
        return view('pages.a-propos');
    }

    public function legal(): View
    {
        return view('pages.mentions-legales');
    }

    public function privacy(): View
    {
        return view('pages.confidentialite');
    }

    /** Sélecteur de langue des visiteurs (les comptes ont leur préférence de profil). */
    public function switchLocale(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['fr', 'en'], true), 404);

        $request->session()->put('locale', $locale);

        return redirect()->back(fallback: route('pages.home'));
    }
}
