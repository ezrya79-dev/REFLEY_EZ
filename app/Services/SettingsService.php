<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Réglages applicatifs pilotés par la base de données.
 *
 * Trois couches de lecture : mémoïsation par requête (le layout et la barre
 * latérale relisent les mêmes clés), cache persistant, puis ligne `settings`
 * (déchiffrée si nécessaire). En dernier recours, repli sur config('refley.*')
 * pour qu'une base fraîche démarre avec des valeurs saines.
 */
class SettingsService
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $value = Cache::rememberForever('settings.'.$key, function () use ($key) {
            $setting = Setting::query()->where('key', $key)->first();

            if ($setting === null) {
                return null;
            }

            $raw = $setting->value;

            if ($setting->is_encrypted) {
                try {
                    $raw = Crypt::decryptString($raw);
                } catch (DecryptException) {
                    // APP_KEY tournée : la valeur est perdue, on la traite
                    // comme absente plutôt que de faire tomber l'application.
                    return null;
                }
            }

            return json_decode($raw, true);
        });

        $value ??= config('refley.'.$key, $default);

        return $this->memo[$key] = $value;
    }

    public function set(string $key, mixed $value, bool $encrypted = false): void
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR);

        Setting::query()->updateOrCreate(['key' => $key], [
            'value' => $encrypted ? Crypt::encryptString($json) : $json,
            'is_encrypted' => $encrypted,
        ]);

        $this->bust($key);
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();

        $this->bust($key);
    }

    /** Un secret est-il déjà enregistré ? (affichage « configuré ✓ » sans le révéler) */
    public function isConfigured(string $key): bool
    {
        return $this->get($key) !== null;
    }

    private function bust(string $key): void
    {
        unset($this->memo[$key]);
        Cache::forget('settings.'.$key);
    }
}
