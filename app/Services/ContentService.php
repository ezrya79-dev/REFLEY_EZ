<?php

namespace App\Services;

use App\Enums\ContentType;
use App\Models\ContentBlock;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Contenus éditoriaux du site. Même architecture de lecture que
 * SettingsService : mémo par requête → cache persistant → base → défaut
 * déclaré dans le gabarit Blade. Repli de locale : une traduction EN
 * absente affiche le FR plutôt qu'un trou.
 */
class ContentService
{
    private const int MAX_REVISIONS = 20;

    /** @var array<string, mixed> */
    private array $memo = [];

    private ?MarkdownConverter $markdown = null;

    public function get(string $page, string $key, string $locale, mixed $default = null): mixed
    {
        $value = $this->raw($page, $key, $locale);

        // Repli de locale : en → fr.
        if ($value === null && $locale !== 'fr') {
            $value = $this->raw($page, $key, 'fr');
        }

        return $value ?? $default;
    }

    public function set(string $page, string $key, string $locale, ContentType $type, mixed $value, User $author): void
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR);

        $block = ContentBlock::query()->where([
            'page' => $page,
            'key' => $key,
            'locale' => $locale,
        ])->first();

        if ($block !== null) {
            if ($block->value === $json) {
                return; // Rien ne change : pas de révision fantôme.
            }

            // L'ancienne valeur devient une révision, plafonnée à 20 par bloc.
            $block->revisions()->create([
                'value' => $block->value,
                'updated_by' => $author->id,
                'created_at' => now(),
            ]);

            $block->revisions()
                ->orderByDesc('id')
                ->skip(self::MAX_REVISIONS)
                ->take(PHP_INT_MAX)
                ->get()
                ->each(fn (ContentRevision $old) => $old->delete());

            $block->update(['value' => $json, 'type' => $type->value, 'updated_by' => $author->id]);
        } else {
            ContentBlock::query()->create([
                'page' => $page,
                'key' => $key,
                'locale' => $locale,
                'type' => $type->value,
                'value' => $json,
                'updated_by' => $author->id,
            ]);
        }

        $this->bust($page, $key, $locale);
    }

    /** Restaure une révision ; l'état actuel devient lui-même une révision. */
    public function revert(ContentRevision $revision, User $author): void
    {
        $block = $revision->block()->firstOrFail();

        $this->set(
            $block->page,
            $block->key,
            $block->locale,
            ContentType::from($block->type),
            json_decode($revision->value, true),
            $author,
        );
    }

    /**
     * Markdown restreint → HTML sûr : le HTML saisi est échappé, les liens
     * dangereux (javascript:) sont neutralisés. Le rendu est mis en cache.
     */
    public function renderMarkdown(string $source): string
    {
        return Cache::rememberForever(
            'content.md.'.hash('sha256', $source),
            fn (): string => (string) $this->markdownConverter()->convert($source),
        );
    }

    private function raw(string $page, string $key, string $locale): mixed
    {
        $memoKey = $page.'|'.$key.'|'.$locale;

        if (array_key_exists($memoKey, $this->memo)) {
            return $this->memo[$memoKey];
        }

        $value = Cache::rememberForever('content.'.$memoKey, function () use ($page, $key, $locale) {
            $block = ContentBlock::query()->where([
                'page' => $page,
                'key' => $key,
                'locale' => $locale,
            ])->first();

            return $block === null ? null : json_decode($block->value, true);
        });

        return $this->memo[$memoKey] = $value;
    }

    private function bust(string $page, string $key, string $locale): void
    {
        $memoKey = $page.'|'.$key.'|'.$locale;
        unset($this->memo[$memoKey]);
        Cache::forget('content.'.$memoKey);
    }

    private function markdownConverter(): MarkdownConverter
    {
        if ($this->markdown === null) {
            $environment = new Environment([
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 20,
            ]);
            $environment->addExtension(new CommonMarkCoreExtension);

            $this->markdown = new MarkdownConverter($environment);
        }

        return $this->markdown;
    }
}
