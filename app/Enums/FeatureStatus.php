<?php

namespace App\Enums;

/** Cycle de vie d'une idée de la roadmap. L'ordre des cas = ordre des colonnes. */
enum FeatureStatus: string
{
    case Proposed = 'proposed';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Shipped = 'shipped';
    case Declined = 'declined';

    public function labelKey(): string
    {
        return 'roadmap.status'.str_replace('_', '', ucwords($this->value, '_'));
    }

    /** Variante de badge du design system. */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Proposed => 'neutral',
            self::Accepted => 'accent',
            self::InProgress => 'warn',
            self::Shipped => 'success',
            self::Declined => 'danger',
        };
    }

    /**
     * Colonnes du tableau visuel (les refusées vivent dans une section repliée).
     *
     * @return array<int, self>
     */
    public static function boardColumns(): array
    {
        return [self::Proposed, self::Accepted, self::InProgress, self::Shipped];
    }
}
