<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\BrandIconService;
use App\Services\BrandingService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly BrandingService $branding,
    ) {}

    /** Écran des réglages : marque, matrice RBAC, connecteurs. */
    public function index(): View
    {
        return view('admin.settings.index', [
            'branding' => $this->branding,
            'accents' => (array) config('refley.accents'),
            'roles' => UserRole::cases(),
            'permissions' => Permission::cases(),
            'smtpConfigured' => $this->settings->isConfigured('connectors.smtp'),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $presets = array_keys((array) config('refley.accents'));

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'accent_preset' => ['required', Rule::in([...$presets, 'custom'])],
            'accent_custom' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/', 'required_if:accent_preset,custom'],
            'email_from_name' => ['required', 'string', 'max:120'],
            'email_from_address' => ['required', 'string', 'email', 'max:255'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ]);

        $this->settings->set('branding.app_name', $validated['app_name']);
        $this->settings->set('branding.accent_preset', $validated['accent_preset']);
        $this->settings->set('branding.accent_custom', $validated['accent_custom'] ?? null);
        $this->settings->set('branding.email_from_name', $validated['email_from_name']);
        $this->settings->set('branding.email_from_address', $validated['email_from_address']);

        if ($request->hasFile('logo')) {
            $path = app(BrandIconService::class)->store($request->file('logo'), $this->branding->logoPath());
            $this->settings->set('branding.logo_path', $path);
        }

        return redirect()->route('settings.index')->with('status', __('settings.brandingSaved'));
    }

    public function deleteLogo(): RedirectResponse
    {
        app(BrandIconService::class)->delete($this->branding->logoPath());
        $this->settings->set('branding.logo_path', null);

        return redirect()->route('settings.index')->with('status', __('settings.logoDeleted'));
    }

    /**
     * Connecteur SMTP : identifiants chiffrés, jamais réaffichés (écriture
     * seule). Un champ mot de passe vide conserve le secret existant.
     */
    public function updateSmtp(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageConnectors->value);

        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['required', Rule::in(['none', 'tls'])],
        ]);

        $existing = (array) ($this->settings->get('connectors.smtp') ?? []);

        $this->settings->set('connectors.smtp', [
            'host' => $validated['host'],
            'port' => (int) $validated['port'],
            'username' => $validated['username'] ?? '',
            'password' => ($validated['password'] ?? '') !== ''
                ? $validated['password']
                : ($existing['password'] ?? ''),
            'encryption' => $validated['encryption'],
        ], encrypted: true);

        return redirect()->route('settings.index')->with('status', __('settings.smtpSaved'));
    }

    public function testSmtp(Request $request, \App\Contracts\SmtpProbe $probe): RedirectResponse
    {
        Gate::authorize(Permission::ManageConnectors->value);

        $smtp = $this->settings->get('connectors.smtp');

        if (! is_array($smtp) || ! isset($smtp['host'], $smtp['port'])) {
            return redirect()->route('settings.index')->with('smtp_test_error', __('settings.smtpNotConfigured'));
        }

        $result = $probe->probe((string) $smtp['host'], (int) $smtp['port']);

        return redirect()
            ->route('settings.index')
            ->with($result->ok ? 'smtp_test_ok' : 'smtp_test_error', $result->message);
    }
}
