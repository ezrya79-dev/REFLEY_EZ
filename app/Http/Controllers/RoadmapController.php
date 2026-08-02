<?php

namespace App\Http\Controllers;

use App\Enums\FeatureDifficulty;
use App\Enums\FeaturePriority;
use App\Enums\FeatureStatus;
use App\Enums\Permission;
use App\Models\FeatureRequest;
use App\Services\RoadmapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoadmapController extends Controller
{
    public function __construct(private readonly RoadmapService $roadmap) {}

    /** Tableau visuel : une colonne par statut, tri par votes décroissants. */
    public function index(Request $request): View
    {
        $categories = (array) config('refley.roadmap_categories');
        $category = $request->query('categorie');

        if (! in_array($category, $categories, true)) {
            $category = null;
        }

        $features = FeatureRequest::query()
            ->withCount(['votes', 'comments'])
            ->with('votes')
            ->when($category !== null, fn ($query) => $query->where('category', $category))
            ->orderByDesc('votes_count')
            ->orderBy('created_at')
            ->get();

        return view('roadmap.index', [
            'columns' => FeatureStatus::boardColumns(),
            'features' => $features->groupBy(fn (FeatureRequest $feature): string => $feature->status->value),
            'declined' => $features->where('status', FeatureStatus::Declined),
            'categories' => $categories,
            'activeCategory' => $category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::in((array) config('refley.roadmap_categories'))],
        ]);

        $feature = $this->roadmap->propose($request->user(), $validated);

        return redirect()->route('roadmap.show', $feature)->with('status', __('roadmap.proposed'));
    }

    public function show(FeatureRequest $feature): View
    {
        $feature->load(['author', 'votes', 'comments.author'])->loadCount('votes');

        return view('roadmap.show', [
            'feature' => $feature,
            'statuses' => FeatureStatus::cases(),
            'priorities' => FeaturePriority::cases(),
            'difficulties' => FeatureDifficulty::cases(),
        ]);
    }

    public function vote(Request $request, FeatureRequest $feature): RedirectResponse
    {
        $this->roadmap->toggleVote($request->user(), $feature);

        return redirect()->back(fallback: route('roadmap.show', $feature));
    }

    public function comment(Request $request, FeatureRequest $feature): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $feature->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return redirect()->route('roadmap.show', $feature)->with('status', __('roadmap.commented'));
    }

    /** Arbitrage — réservé au gate roadmap.manage, tracé dans le journal d'audit. */
    public function update(Request $request, FeatureRequest $feature): RedirectResponse
    {
        Gate::authorize(Permission::ManageRoadmap->value);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(FeatureStatus::class)],
            'priority' => ['required', Rule::enum(FeaturePriority::class)],
            'difficulty' => ['required', Rule::enum(FeatureDifficulty::class)],
        ]);

        $this->roadmap->arbitrate(
            $request->user(),
            $feature,
            FeatureStatus::from($validated['status']),
            FeaturePriority::from($validated['priority']),
            FeatureDifficulty::from($validated['difficulty']),
        );

        return redirect()->route('roadmap.show', $feature)->with('status', __('roadmap.arbitrated'));
    }

    public function destroy(Request $request, FeatureRequest $feature): RedirectResponse
    {
        $this->roadmap->delete($request->user(), $feature);

        return redirect()->route('roadmap.index')->with('status', __('roadmap.deleted'));
    }
}
