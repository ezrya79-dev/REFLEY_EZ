<?php

namespace App\Http\Controllers;

use App\Enums\Theme;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Page « Mon profil » : chaque utilisateur ne touche que sa propre ligne —
 * aucun identifiant en paramètre, toujours $request->user().
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function updatePhoto(Request $request, AvatarService $avatars): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $avatars->store($request->user(), $request->file('photo'));

        return redirect()->route('profile.show')->with('status', __('profile.photoUpdated'));
    }

    public function deletePhoto(Request $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->delete($request->user());

        return redirect()->route('profile.show')->with('status', __('profile.photoDeleted'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        // Trace d'audit sans jamais journaliser le mot de passe lui-même.
        Log::channel('auth')->info('profile.password_changed', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('profile.show')->with('status', __('profile.passwordUpdated'));
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::enum(Theme::class)],
            'locale' => ['required', Rule::in(['fr', 'en'])],
        ]);

        $request->user()->update([
            'theme' => $validated['theme'],
            'locale' => $validated['locale'],
        ]);

        return redirect()->route('profile.show')->with('status', __('profile.preferencesUpdated'));
    }
}
