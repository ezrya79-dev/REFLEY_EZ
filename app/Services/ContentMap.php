<?php

namespace App\Services;

use App\Enums\ContentType;
use Symfony\Component\Finder\Finder;

/**
 * Carte des zones éditables, découverte en scannant les déclarations
 * <x-content*> des gabarits Blade. Le gabarit déclare, l'admin remplit :
 * aucune clé saisie à la main, aucun contenu orphelin possible.
 */
class ContentMap
{
    /**
     * Scanne les vues et retourne la carte page => zones.
     *
     * @return array<string, array<int, array{key: string, type: string}>>
     */
    public function scan(): array
    {
        $map = [];

        $components = [
            'x-content-markdown' => ContentType::Markdown,
            'x-content-image' => ContentType::Image,
            'x-content' => ContentType::Text,
        ];

        foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
            $source = $file->getContents();

            foreach ($components as $tag => $type) {
                // Attributs page/key dans n'importe quel ordre. Le tag
                // x-content est testé en dernier pour ne pas avaler les
                // variantes -markdown / -image.
                $pattern = '/<'.preg_quote($tag, '/').'(?![\w-])([^>]*)>/';

                if (! preg_match_all($pattern, $source, $matches)) {
                    continue;
                }

                foreach ($matches[1] as $attributes) {
                    if ($tag === 'x-content' && preg_match('/^-/', trim($attributes))) {
                        continue;
                    }

                    $page = $this->attribute($attributes, 'page');
                    $key = $this->attribute($attributes, 'key');

                    if ($page === null || $key === null) {
                        continue;
                    }

                    $map[$page][] = ['key' => $key, 'type' => $type->value];
                }
            }
        }

        // Zones SEO implicites : chaque page du registre en dispose sans
        // avoir à les déclarer dans son gabarit.
        foreach (array_keys((array) config('content.pages')) as $page) {
            $map[$page][] = ['key' => 'seo.title', 'type' => ContentType::Text->value];
            $map[$page][] = ['key' => 'seo.description', 'type' => ContentType::Text->value];
        }

        // Déduplique (une zone réutilisée dans deux gabarits) et trie.
        foreach ($map as $page => $zones) {
            $unique = collect($zones)->unique('key')->sortBy('key')->values()->all();
            $map[$page] = $unique;
        }

        ksort($map);
        file_put_contents($this->cachePath(), '<?php return '.var_export($map, true).';');

        return $map;
    }

    /**
     * Zones d'une page (depuis le cache, scan à la demande sinon).
     *
     * @return array<int, array{key: string, type: string}>
     */
    public function zones(string $page): array
    {
        return $this->all()[$page] ?? [];
    }

    /**
     * @return array<string, array<int, array{key: string, type: string}>>
     */
    public function all(): array
    {
        if (is_file($this->cachePath())) {
            /** @var array<string, array<int, array{key: string, type: string}>> */
            return require $this->cachePath();
        }

        return $this->scan();
    }

    private function attribute(string $attributes, string $name): ?string
    {
        if (preg_match('/\b'.$name.'="([^"]+)"/', $attributes, $match)) {
            return $match[1];
        }

        return null;
    }

    private function cachePath(): string
    {
        return base_path('bootstrap/cache/content-map.php');
    }
}
