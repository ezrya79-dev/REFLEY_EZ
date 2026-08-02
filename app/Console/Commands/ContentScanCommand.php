<?php

namespace App\Console\Commands;

use App\Services\ContentMap;
use Illuminate\Console\Command;

/**
 * Reconstruit la carte des zones éditables (exécuté au déploiement et
 * depuis l'écran d'administration des contenus).
 */
class ContentScanCommand extends Command
{
    protected $signature = 'content:scan';

    protected $description = 'Scanner les gabarits et reconstruire la carte des zones de contenu';

    public function handle(ContentMap $map): int
    {
        $result = $map->scan();

        foreach ($result as $page => $zones) {
            $this->line($page.' : '.count($zones).' zone(s)');
        }

        $this->info('Carte des contenus reconstruite.');

        return self::SUCCESS;
    }
}
