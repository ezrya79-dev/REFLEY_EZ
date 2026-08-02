<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\ContentRevision;
use App\Models\Media;
use App\Services\ContentMap;
use App\Services\ContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Édition des contenus du site — gate content.manage (routes). */
class ContentController extends Controller
{
    public function __construct(
        private readonly ContentService $content,
        private readonly ContentMap $map,
    ) {}

    public function index(): View
    {
        return view('admin.content.index', [
            'pages' => (array) config('content.pages'),
            'map' => $this->map->all(),
        ]);
    }

    public function rescan(): RedirectResponse
    {
        $this->map->scan();

        return redirect()->route('content.index')->with('status', __('content.rescanned'));
    }

    public function edit(Request $request, string $page): View
    {
        abort_unless(array_key_exists($page, (array) config('content.pages')), 404);

        $locale = $request->query('locale') === 'en' ? 'en' : 'fr';
        $zones = $this->map->zones($page);

        $values = [];

        foreach ($zones as $zone) {
            $values[$zone['key']] = $this->content->get($page, $zone['key'], $locale);
        }

        return view('admin.content.edit', [
            'page' => $page,
            'pageConfig' => config('content.pages.'.$page),
            'locale' => $locale,
            'zones' => $zones,
            'values' => $values,
            'mediaLibrary' => Media::query()->latest()->get(),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        abort_unless(array_key_exists($page, (array) config('content.pages')), 404);

        $locale = $request->input('locale') === 'en' ? 'en' : 'fr';
        $zones = collect($this->map->zones($page))->keyBy('key');

        $validated = $request->validate([
            'blocks' => ['array'],
            'blocks.*' => ['nullable', 'string', 'max:20000'],
            'images' => ['array'],
            'images.*' => ['nullable', 'integer', 'exists:media,id'],
        ]);

        foreach ($validated['blocks'] ?? [] as $key => $value) {
            $zone = $zones->get($key);

            if ($zone === null || $zone['type'] === ContentType::Image->value) {
                continue; // Clé inconnue de la carte : ignorée, jamais créée.
            }

            $this->content->set($page, $key, $locale, ContentType::from($zone['type']), (string) $value, $request->user());
        }

        foreach ($validated['images'] ?? [] as $key => $mediaId) {
            $zone = $zones->get($key);

            if ($zone === null || $zone['type'] !== ContentType::Image->value) {
                continue;
            }

            $value = $mediaId === null ? null : ['media_id' => (int) $mediaId];
            $this->content->set($page, $key, $locale, ContentType::Image, $value, $request->user());
        }

        return redirect()
            ->route('content.edit', ['page' => $page, 'locale' => $locale])
            ->with('status', __('content.saved'));
    }

    /** Aperçu serveur du markdown saisi (pour l'éditeur, sans lib cliente). */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(['source' => ['required', 'string', 'max:20000']]);

        return response()->json(['html' => $this->content->renderMarkdown($validated['source'])]);
    }

    public function history(string $page, string $key): View
    {
        $blocks = ContentBlock::query()
            ->where('page', $page)
            ->where('key', $key)
            ->with(['revisions.author'])
            ->get();

        abort_if($blocks->isEmpty(), 404);

        return view('admin.content.history', [
            'page' => $page,
            'key' => $key,
            'blocks' => $blocks,
        ]);
    }

    public function revert(Request $request, ContentRevision $revision): RedirectResponse
    {
        $block = $revision->block()->firstOrFail();

        $this->content->revert($revision, $request->user());

        return redirect()
            ->route('content.edit', ['page' => $block->page, 'locale' => $block->locale])
            ->with('status', __('content.reverted'));
    }
}
