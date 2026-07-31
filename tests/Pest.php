<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/*
 * Configuration Pest : les tests Feature démarrent une application Laravel
 * avec base rafraîchie ; l'horloge est gelée sur une date de référence pour
 * des assertions déterministes.
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00')))
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00')))
    ->in('Unit');
