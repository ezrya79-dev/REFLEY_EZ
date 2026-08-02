<?php

use App\Enums\ContentType;
use App\Models\ContentBlock;
use App\Models\User;
use App\Services\ContentService;

beforeEach(function () {
    $this->service = app(ContentService::class);
    $this->author = User::factory()->create();
});

test('an empty database falls back to the template default', function () {
    expect($this->service->get('accueil', 'hero.titre', 'fr', 'Défaut'))->toBe('Défaut');
});

test('set then get round-trips and busts the cache', function () {
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Bonjour', $this->author);

    expect($this->service->get('accueil', 'hero.titre', 'fr'))->toBe('Bonjour')
        ->and(app(ContentService::class)->get('accueil', 'hero.titre', 'fr'))->toBe('Bonjour');

    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Rebonjour', $this->author);

    expect(app(ContentService::class)->get('accueil', 'hero.titre', 'fr'))->toBe('Rebonjour');
});

test('a missing english value falls back to french', function () {
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Bonjour', $this->author);

    expect($this->service->get('accueil', 'hero.titre', 'en', 'default'))->toBe('Bonjour');

    $this->service->set('accueil', 'hero.titre', 'en', ContentType::Text, 'Hello', $this->author);

    expect(app(ContentService::class)->get('accueil', 'hero.titre', 'en'))->toBe('Hello');
});

test('each update archives the previous value as a revision', function () {
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'V1', $this->author);
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'V2', $this->author);
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'V3', $this->author);

    $block = ContentBlock::query()->firstOrFail();

    expect($block->revisions()->count())->toBe(2)
        ->and(json_decode($block->revisions()->first()->value, true))->toBe('V2');
});

test('saving an identical value creates no ghost revision', function () {
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Idem', $this->author);
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Idem', $this->author);

    expect(ContentBlock::query()->firstOrFail()->revisions()->count())->toBe(0);
});

test('revisions are capped at twenty per block', function () {
    foreach (range(0, 25) as $i) {
        $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'V'.$i, $this->author);
    }

    expect(ContentBlock::query()->firstOrFail()->revisions()->count())->toBe(20);
});

test('revert restores a revision and archives the current value', function () {
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Ancienne', $this->author);
    $this->service->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Nouvelle', $this->author);

    $revision = ContentBlock::query()->firstOrFail()->revisions()->firstOrFail();

    $this->service->revert($revision, $this->author);

    expect(app(ContentService::class)->get('accueil', 'hero.titre', 'fr'))->toBe('Ancienne');
});

test('markdown renders safely: raw html is escaped and unsafe links dropped', function () {
    $html = $this->service->renderMarkdown("**Gras** <script>alert('xss')</script>\n\n[lien](javascript:alert(1))");

    expect($html)->toContain('<strong>Gras</strong>')
        ->not->toContain('<script>')
        ->not->toContain('javascript:alert');
});
