<?php

namespace App\Enums;

enum Theme: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public function labelKey(): string
    {
        return 'profile.theme'.ucfirst($this->value);
    }
}
