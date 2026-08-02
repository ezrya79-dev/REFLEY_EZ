<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $feature_request_id
 * @property int $user_id
 */
#[Fillable(['feature_request_id', 'user_id'])]
class FeatureVote extends Model {}
