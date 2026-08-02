<?php

namespace App\Enums;

enum FeaturePriority: string
{
    case None = 'none';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function labelKey(): string
    {
        return 'roadmap.priority'.ucfirst($this->value);
    }
}
