<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

/*
 * Configuration Rector — modernisation et détection de code obsolète.
 *
 * En CI, Rector tourne en mode « dry-run » (rapport seul, non bloquant) : il
 * signale les modernisations possibles sans réécrire le code automatiquement.
 * En local, `vendor/bin/rector process` applique les corrections.
 *
 * Périmètre volontairement prudent : code mort, qualité de code, montée de
 * version PHP 8.2 et règles Laravel sûres. Les règles trop intrusives (typage
 * automatique agressif) sont laissées de côté pour éviter les régressions.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        // Le cache et les artefacts n'ont pas à être analysés.
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
    ])
    ->withPhpSets(php82: true)
    ->withSets([
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        LaravelSetList::LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_IF_HELPERS,
    ])
    ->withImportNames(removeUnusedImports: true);
