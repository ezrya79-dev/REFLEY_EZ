<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $page
 * @property string $key
 * @property string $locale
 * @property string $type
 * @property string $value JSON
 * @property int|null $updated_by
 */
#[Fillable(['page', 'key', 'locale', 'type', 'value', 'updated_by'])]
class ContentBlock extends Model
{
    /**
     * @return HasMany<ContentRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class)->orderByDesc('id');
    }
}
