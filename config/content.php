<?php

/*
 * Registre des pages éditoriales. La structure d'une page est un gabarit
 * Blade (resources/views/pages/{slug}.blade.php) ; ses zones éditables sont
 * découvertes en scannant les composants <x-content*> (content:scan).
 * Ajouter une page = une entrée ici + un gabarit + une route.
 */
return [

    'pages' => [
        'accueil' => ['titleKey' => 'content.pageAccueil', 'route' => 'pages.home'],
        'a-propos' => ['titleKey' => 'content.pageApropos', 'route' => 'pages.about'],
        'mentions-legales' => ['titleKey' => 'content.pageMentions', 'route' => 'pages.legal'],
        'confidentialite' => ['titleKey' => 'content.pagePrivacy', 'route' => 'pages.privacy'],
    ],

];
