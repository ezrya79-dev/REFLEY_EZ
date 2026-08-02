<?php

use App\Enums\ContentType;
use App\Models\User;
use App\Services\ContentService;

test('the public pages render for visitors without an account', function () {
    $this->get('/')->assertOk()->assertSee('public-shell');
    $this->get('/a-propos')->assertOk();
    $this->get('/mentions-legales')->assertOk();
    $this->get('/confidentialite')->assertOk();
});

test('template defaults display on a fresh database', function () {
    $this->get('/')->assertSee('Votre équipe, un seul espace');
});

test('edited content replaces the default without a deploy', function () {
    $author = User::factory()->admin()->create();
    app(ContentService::class)->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Titre édité', $author);

    $this->get('/')->assertSee('Titre édité')->assertDontSee('Votre équipe, un seul espace');
});

test('markdown content renders as safe html on the page', function () {
    $author = User::factory()->admin()->create();
    app(ContentService::class)->set(
        'a-propos',
        'corps',
        'fr',
        ContentType::Markdown,
        "Du **gras** et <script>alert('xss')</script>",
        $author,
    );

    $response = $this->get('/a-propos');

    $response->assertSee('<strong>gras</strong>', escape: false);
    expect($response->getContent())->not->toContain("<script>alert('xss')</script>");
});

test('visitors can switch language and the choice sticks in session', function () {
    $this->get('/langue/en')->assertRedirect();
    $this->get('/a-propos')->assertSee('Legal notice');

    $this->get('/langue/fr');
    $this->get('/a-propos')->assertSee('Mentions légales');
});

test('an unknown locale is rejected', function () {
    $this->get('/langue/de')->assertNotFound();
});

test('english content falls back to french when untranslated', function () {
    $author = User::factory()->admin()->create();
    app(ContentService::class)->set('a-propos', 'titre', 'fr', ContentType::Text, 'Titre FR seulement', $author);

    $this->get('/langue/en');
    $this->get('/a-propos')->assertSee('Titre FR seulement');
});

test('seo title and description flow into the head', function () {
    $author = User::factory()->admin()->create();
    $content = app(ContentService::class);
    $content->set('accueil', 'seo.title', 'fr', ContentType::Text, 'Refley — espace d\'équipe', $author);
    $content->set('accueil', 'seo.description', 'fr', ContentType::Text, 'La description SEO.', $author);

    $this->get('/')
        ->assertSee('<title>Refley — espace d&#039;équipe</title>', escape: false)
        ->assertSee('name="description" content="La description SEO."', escape: false);
});
