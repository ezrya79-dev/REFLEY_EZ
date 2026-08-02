<?php

namespace App\Models;

use App\Enums\FeatureDifficulty;
use App\Enums\FeaturePriority;
use App\Enums\FeatureStatus;
use Database\Factories\FeatureRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $category
 * @property FeatureStatus $status
 * @property FeaturePriority $priority
 * @property FeatureDifficulty $difficulty
 * @property int|null $user_id
 * @property-read int|null $votes_count
 * @property-read int|null $comments_count
 */
#[Fillable(['title', 'description', 'category', 'status', 'priority', 'difficulty', 'user_id'])]
class FeatureRequest extends Model
{
    /** @use HasFactory<FeatureRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FeatureStatus::class,
            'priority' => FeaturePriority::class,
            'difficulty' => FeatureDifficulty::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<FeatureVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(FeatureVote::class);
    }

    /**
     * @return HasMany<FeatureComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(FeatureComment::class);
    }

    public function hasVoteFrom(User $user): bool
    {
        return $this->votes->contains('user_id', $user->id);
    }

    public function categoryLabelKey(): string
    {
        return 'roadmap.cat'.str_replace('-', '', ucwords($this->category, '-'));
    }
}
