<?php

namespace App\Enums;

enum FeatureDifficulty: string
{
    case Unknown = 'unknown';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';

    public function labelKey(): string
    {
        return 'roadmap.difficulty'.ucfirst($this->value);
    }
}
