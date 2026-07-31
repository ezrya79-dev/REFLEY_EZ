<?php

/*
 * Valeurs par défaut des réglages applicatifs. La base de données (table
 * `settings`, éditée depuis /reglages) prime toujours sur ce fichier : il
 * sert de repli pour qu'une installation fraîche démarre sans configuration.
 */
return [

    'branding' => [
        'app_name' => env('APP_NAME', 'Refley'),
        'accent_preset' => 'teal',
        'accent_custom' => null,
        'logo_path' => null,
        'email_from_name' => env('MAIL_FROM_NAME', 'Refley'),
        'email_from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    ],

    /*
     * Palette d'accents proposée dans l'écran de marque. Chaque préréglage
     * n'expose qu'une seule couleur : les variantes (survol, teinte claire)
     * sont dérivées en CSS via color-mix().
     */
    'accents' => [
        'teal' => '#0f766e',
        'indigo' => '#4f46e5',
        'violet' => '#7c3aed',
        'rose' => '#be123c',
        'amber' => '#b45309',
        'emerald' => '#047857',
        'slate' => '#334155',
    ],

];
