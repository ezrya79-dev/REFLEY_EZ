<?php

use App\Services\ContentMap;

test('scanning discovers the zones declared in the page templates', function () {
    $map = (new ContentMap)->scan();

    $accueil = collect($map['accueil'] ?? []);

    expect($accueil->firstWhere('key', 'hero.titre'))->toMatchArray(['type' => 'text'])
        ->and($accueil->firstWhere('key', 'hero.corps'))->toMatchArray(['type' => 'markdown'])
        ->and($accueil->firstWhere('key', 'hero.visuel'))->toMatchArray(['type' => 'image']);
});

test('every registered page carries the implicit seo zones', function () {
    $map = (new ContentMap)->scan();

    foreach (array_keys((array) config('content.pages')) as $page) {
        $keys = collect($map[$page] ?? [])->pluck('key');

        expect($keys)->toContain('seo.title')->toContain('seo.description');
    }
});

test('zones() reads from the cached map', function () {
    $service = new ContentMap;
    $service->scan();

    expect($service->zones('a-propos'))->not->toBeEmpty()
        ->and($service->zones('page-inconnue'))->toBe([]);
});
