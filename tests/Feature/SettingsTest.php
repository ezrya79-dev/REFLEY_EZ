<?php

use App\Contracts\SmtpProbe;
use App\Data\ProbeResult;
use App\Models\Setting;
use App\Models\User;
use App\Services\BrandIconService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the settings screen shows branding, connectors and the permission matrix', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/reglages')
        ->assertOk()
        ->assertSee(__('settings.appName'))
        ->assertSee(__('settings.smtpTitle'))
        ->assertSee(__('rbac.matrixTitle'))
        ->assertSee('users.manage');
});

test('saving branding renames the app everywhere without a deploy', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/reglages/marque', [
        'app_name' => 'Cabinet Lumière',
        'accent_preset' => 'indigo',
        'email_from_name' => 'Cabinet Lumière',
        'email_from_address' => 'contact@lumiere.fr',
    ])->assertRedirect('/reglages');

    $this->actingAs($admin)->get('/tableau-de-bord')
        ->assertSee('Cabinet Lumière')
        ->assertSee('data-accent="indigo"', escape: false);
});

test('a custom accent hex is applied inline', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/reglages/marque', [
        'app_name' => 'Refley',
        'accent_preset' => 'custom',
        'accent_custom' => '#a1b2c3',
        'email_from_name' => 'Refley',
        'email_from_address' => 'hello@refley.fr',
    ]);

    $this->actingAs($admin)->get('/tableau-de-bord')
        ->assertSee('--accent: #a1b2c3', escape: false);
});

test('an invalid custom hex is rejected', function () {
    $this->actingAs(User::factory()->admin()->create())->post('/reglages/marque', [
        'app_name' => 'Refley',
        'accent_preset' => 'custom',
        'accent_custom' => 'rouge',
        'email_from_name' => 'Refley',
        'email_from_address' => 'hello@refley.fr',
    ])->assertSessionHasErrors('accent_custom');
});

test('uploading a logo derives favicons and pwa icons for the manifest and head', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/reglages/marque', [
        'app_name' => 'Refley',
        'accent_preset' => 'teal',
        'email_from_name' => 'Refley',
        'email_from_address' => 'hello@refley.fr',
        'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
    ])->assertRedirect('/reglages');

    foreach (BrandIconService::SIZES as $size) {
        Storage::disk('public')->assertExists(BrandIconService::iconPath($size));
    }

    $this->actingAs($admin)->get('/tableau-de-bord')->assertSee('icon-32.png');
});

test('smtp credentials are encrypted at rest and never echoed back', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/reglages/connecteurs/smtp', [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'mailer',
        'password' => 'Ultra-Secret-Password',
        'encryption' => 'tls',
    ])->assertRedirect('/reglages');

    $row = Setting::query()->where('key', 'connectors.smtp')->firstOrFail();
    expect($row->is_encrypted)->toBeTrue()
        ->and($row->value)->not->toContain('Ultra-Secret-Password');

    // L'écran ne ré-affiche jamais le secret : écriture seule.
    $this->actingAs($admin)->get('/reglages')
        ->assertOk()
        ->assertDontSee('Ultra-Secret-Password')
        ->assertSee(__('settings.smtpPasswordConfigured'));
});

test('saving smtp with a blank password keeps the existing secret', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/reglages/connecteurs/smtp', [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'mailer',
        'password' => 'Keep-This-Secret',
        'encryption' => 'tls',
    ]);

    $this->actingAs($admin)->post('/reglages/connecteurs/smtp', [
        'host' => 'smtp2.example.com',
        'port' => 465,
        'username' => 'mailer',
        'password' => '',
        'encryption' => 'tls',
    ]);

    $smtp = app(App\Services\SettingsService::class)->get('connectors.smtp');
    expect($smtp['host'])->toBe('smtp2.example.com')
        ->and($smtp['password'])->toBe('Keep-This-Secret');
});

test('the smtp test button reports success without persisting anything', function () {
    $admin = User::factory()->admin()->create();

    $this->app->bind(SmtpProbe::class, fn () => new class implements SmtpProbe
    {
        public function probe(string $host, int $port, int $timeoutSeconds = 5): ProbeResult
        {
            return ProbeResult::success('220 smtp.example.com ready');
        }
    });

    $this->actingAs($admin)->post('/reglages/connecteurs/smtp', [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => '',
        'password' => 'secret-value',
        'encryption' => 'tls',
    ]);

    $countBefore = Setting::query()->count();

    $this->actingAs($admin)->post('/reglages/connecteurs/smtp/test')
        ->assertRedirect('/reglages')
        ->assertSessionHas('smtp_test_ok');

    expect(Setting::query()->count())->toBe($countBefore);
});

test('the smtp test reports failures', function () {
    $admin = User::factory()->admin()->create();

    $this->app->bind(SmtpProbe::class, fn () => new class implements SmtpProbe
    {
        public function probe(string $host, int $port, int $timeoutSeconds = 5): ProbeResult
        {
            return ProbeResult::failure('Connection refused');
        }
    });

    $this->actingAs($admin)->post('/reglages/connecteurs/smtp', [
        'host' => 'down.example.com',
        'port' => 587,
        'username' => '',
        'password' => 'x',
        'encryption' => 'tls',
    ]);

    $this->actingAs($admin)->post('/reglages/connecteurs/smtp/test')
        ->assertSessionHas('smtp_test_error');
});

test('testing an unconfigured connector explains itself', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/reglages/connecteurs/smtp/test')
        ->assertSessionHas('smtp_test_error', __('settings.smtpNotConfigured'));
});

test('non-admins cannot read or write settings', function () {
    foreach ([User::factory()->manager()->create(), User::factory()->create()] as $actor) {
        $this->actingAs($actor)->get('/reglages')->assertForbidden();
        $this->actingAs($actor)->post('/reglages/marque', [])->assertForbidden();
        $this->actingAs($actor)->post('/reglages/connecteurs/smtp', [])->assertForbidden();
    }
});
