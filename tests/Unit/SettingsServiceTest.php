<?php

use App\Models\Setting;
use App\Services\SettingsService;

beforeEach(function () {
    $this->service = app(SettingsService::class);
});

test('falls back to config for a fresh database', function () {
    expect($this->service->get('branding.accent_preset'))->toBe('teal')
        ->and($this->service->get('unknown.key', 'dflt'))->toBe('dflt');
});

test('set then get round-trips through the database', function () {
    $this->service->set('branding.app_name', 'Clinique du Parc');

    expect($this->service->get('branding.app_name'))->toBe('Clinique du Parc')
        ->and(Setting::query()->where('key', 'branding.app_name')->exists())->toBeTrue();
});

test('encrypted values are ciphered at rest and decrypted on read', function () {
    $secret = ['password' => 'super-secret'];

    $this->service->set('connectors.smtp', $secret, encrypted: true);

    $row = Setting::query()->where('key', 'connectors.smtp')->firstOrFail();

    expect($row->is_encrypted)->toBeTrue()
        ->and($row->value)->not->toContain('super-secret')
        ->and($this->service->get('connectors.smtp'))->toBe($secret);
});

test('an undecryptable value reads as missing instead of crashing', function () {
    // Valeur illisible (APP_KEY tournée, ligne corrompue…) : le service doit
    // la traiter comme absente et retomber sur le défaut, pas planter.
    Setting::query()->create([
        'key' => 'connectors.smtp',
        'value' => 'garbage-that-cannot-be-decrypted',
        'is_encrypted' => true,
    ]);

    expect(app(SettingsService::class)->get('connectors.smtp', 'fallback'))->toBe('fallback');
});

test('writes bust the cache so reads are never stale', function () {
    $this->service->set('branding.app_name', 'Avant');
    expect($this->service->get('branding.app_name'))->toBe('Avant');

    $this->service->set('branding.app_name', 'Après');
    expect($this->service->get('branding.app_name'))->toBe('Après')
        ->and(app(SettingsService::class)->get('branding.app_name'))->toBe('Après');
});

test('forget removes the row and the cached value', function () {
    $this->service->set('branding.app_name', 'Temporaire');
    $this->service->forget('branding.app_name');

    expect(Setting::query()->where('key', 'branding.app_name')->exists())->toBeFalse()
        ->and($this->service->get('branding.app_name'))->toBe(config('refley.branding.app_name'));
});

test('isConfigured reports presence without exposing the value', function () {
    expect($this->service->isConfigured('connectors.smtp'))->toBeFalse();

    $this->service->set('connectors.smtp', ['password' => 'x'], encrypted: true);

    expect(app(SettingsService::class)->isConfigured('connectors.smtp'))->toBeTrue();
});
