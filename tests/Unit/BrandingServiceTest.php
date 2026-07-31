<?php

use App\Services\BrandingService;
use App\Services\SettingsService;

beforeEach(function () {
    $this->settings = app(SettingsService::class);
    $this->branding = app(BrandingService::class);
});

test('app name defaults from config then follows settings', function () {
    expect($this->branding->appName())->toBe(config('refley.branding.app_name'));

    $this->settings->set('branding.app_name', 'Refley Pro');

    expect($this->branding->appName())->toBe('Refley Pro');
});

test('accent resolves preset colors', function () {
    $this->settings->set('branding.accent_preset', 'indigo');

    expect($this->branding->accentColor())->toBe(config('refley.accents.indigo'));
});

test('accent falls back to the first preset for unknown keys', function () {
    $this->settings->set('branding.accent_preset', 'nope');

    expect($this->branding->accentColor())->toBe(config('refley.accents.teal'));
});

test('custom accent wins when preset is custom', function () {
    $this->settings->set('branding.accent_preset', 'custom');
    $this->settings->set('branding.accent_custom', '#123abc');

    expect($this->branding->accentColor())->toBe('#123abc')
        ->and($this->branding->accentCustom())->toBe('#123abc');
});

test('logo url is null until a logo is stored', function () {
    expect($this->branding->logoUrl())->toBeNull()
        ->and($this->branding->iconUrls())->toBe([]);
});
