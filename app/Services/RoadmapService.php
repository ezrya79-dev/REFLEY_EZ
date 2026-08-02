<?php

namespace App\Services;

use App\Enums\FeatureDifficulty;
use App\Enums\FeaturePriority;
use App\Enums\FeatureStatus;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Roadmap produit : la contribution est ouverte à tous les comptes, seul
 * l'arbitrage (statut / priorité / difficulté) est une permission — et
 * chaque arbitrage laisse une trace : « qui a refusé mon idée » doit
 * toujours avoir une réponse.
 */
class RoadmapService
{
    /**
     * @param  array{title: string, description: string, category: string}  $data
     */
    public function propose(User $author, array $data): FeatureRequest
    {
        return FeatureRequest::query()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'user_id' => $author->id,
        ]);
    }

    /** Vote-bascule : un clic ajoute le vote, le suivant le retire. */
    public function toggleVote(User $user, FeatureRequest $feature): bool
    {
        $existing = $feature->votes()->where('user_id', $user->id)->first();

        if ($existing !== null) {
            $existing->delete();

            return false;
        }

        $feature->votes()->create(['user_id' => $user->id]);

        return true;
    }

    public function arbitrate(
        User $manager,
        FeatureRequest $feature,
        FeatureStatus $status,
        FeaturePriority $priority,
        FeatureDifficulty $difficulty,
    ): void {
        $before = [
            'status' => $feature->status->value,
            'priority' => $feature->priority->value,
            'difficulty' => $feature->difficulty->value,
        ];

        $feature->update([
            'status' => $status,
            'priority' => $priority,
            'difficulty' => $difficulty,
        ]);

        Log::channel('audit')->info('roadmap.arbitrated', [
            'feature_id' => $feature->id,
            'by' => $manager->id,
            'before' => $before,
            'after' => [
                'status' => $status->value,
                'priority' => $priority->value,
                'difficulty' => $difficulty->value,
            ],
        ]);
    }

    /**
     * Suppression : l'auteur peut retirer sa propre idée tant qu'elle est à
     * l'état proposé ; les gestionnaires de la roadmap peuvent tout supprimer.
     * Les votes et commentaires suivent (cascade en base).
     *
     * @throws ValidationException
     */
    public function delete(User $actor, FeatureRequest $feature): void
    {
        $isAuthor = $feature->user_id === $actor->id;
        $canManage = $actor->can(\App\Enums\Permission::ManageRoadmap->value);

        if (! $canManage && (! $isAuthor || $feature->status !== FeatureStatus::Proposed)) {
            throw ValidationException::withMessages(['feature' => __('roadmap.errorCannotDelete')]);
        }

        $feature->delete();

        Log::channel('audit')->info('roadmap.deleted', [
            'feature_id' => $feature->id,
            'title' => $feature->title,
            'by' => $actor->id,
        ]);
    }
}
