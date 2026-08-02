<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Bibliothèque de médias — gate media.manage (routes). */
class MediaController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(): View
    {
        return view('admin.media.index', [
            'items' => Media::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:8192'],
            'alt_fr' => ['nullable', 'string', 'max:255'],
        ]);

        $media = $this->media->store($request->file('file'), $request->user());

        if ($request->filled('alt_fr')) {
            $media->update(['alt_fr' => $request->string('alt_fr')->toString()]);
        }

        return redirect()->route('media.index')->with('status', __('media.uploaded'));
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $validated = $request->validate([
            'alt_fr' => ['nullable', 'string', 'max:255'],
            'alt_en' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($validated);

        return redirect()->route('media.index')->with('status', __('media.updated'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->media->delete($media);

        return redirect()->route('media.index')->with('status', __('media.deleted'));
    }
}
