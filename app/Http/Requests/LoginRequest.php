<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const int MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Tente la connexion : limitation de débit (5/min par e-mail+IP), refus
     * des comptes désactivés, et journal d'audit des succès comme des échecs.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user !== null && ! $user->is_active) {
            $this->fail('auth.failedInactive');
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            $this->fail('auth.failed');
        }

        RateLimiter::clear($this->throttleKey());
        $this->session()->regenerate();

        Log::channel('auth')->info('login.success', [
            'email' => $credentials['email'],
            'ip' => $this->ip(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $messageKey): never
    {
        RateLimiter::hit($this->throttleKey(), 60);

        Log::channel('auth')->warning('login.failed', [
            'email' => (string) $this->input('email'),
            'ip' => $this->ip(),
        ]);

        throw ValidationException::withMessages(['email' => __($messageKey)]);
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        Log::channel('auth')->warning('login.throttled', [
            'email' => (string) $this->input('email'),
            'ip' => $this->ip(),
        ]);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
