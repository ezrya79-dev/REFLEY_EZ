<?php

namespace Database\Factories;

use App\Enums\FeatureDifficulty;
use App\Enums\FeaturePriority;
use App\Enums\FeatureStatus;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureRequest>
 */
class FeatureRequestFactory extends Factory
{
    /**
     * @return array<model-property<FeatureRequest>, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement((array) config('refley.roadmap_categories')),
            'status' => FeatureStatus::Proposed,
            'priority' => FeaturePriority::None,
            'difficulty' => FeatureDifficulty::Unknown,
            'user_id' => User::factory(),
        ];
    }

    public function status(FeatureStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
