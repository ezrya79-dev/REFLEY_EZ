<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Identité visuelle de l'application, lue par les layouts, les e-mails et le
 * manifeste PWA. Tout provient des réglages (base de données) avec repli sur
 * config('refley.branding.*') : l'identité est de la donnée, pas du code.
 */
class BrandingService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function appName(): string
    {
        return (string) $this->settings->get('branding.app_name');
    }

    public function accentPreset(): string
    {
        return (string) $this->settings->get('branding.accent_preset');
    }

    public function accentCustom(): ?string
    {
        $hex = $this->settings->get('branding.accent_custom');

        return is_string($hex) && $hex !== '' ? $hex : null;
    }

    /** Couleur d'accent effective (préréglage résolu ou hexadécimal libre). */
    public function accentColor(): string
    {
        if ($this->accentPreset() === 'custom' && $this->accentCustom() !== null) {
            return $this->accentCustom();
        }

        $presets = (array) config('refley.accents');

        return $presets[$this->accentPreset()] ?? reset($presets);
    }

    public function logoPath(): ?string
    {
        $path = $this->settings->get('branding.logo_path');

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        return $path === null ? null : Storage::disk('public')->url($path);
    }

    /**
     * URLs des icônes dérivées du logo (favicons + PWA), si un logo est posé.
     *
     * @return array<int, string> taille (px) => URL
     */
    public function iconUrls(): array
    {
        if ($this->logoPath() === null) {
            return [];
        }

        $disk = Storage::disk('public');
        $urls = [];

        foreach (BrandIconService::SIZES as $size) {
            $path = BrandIconService::iconPath($size);

            if ($disk->exists($path)) {
                $urls[$size] = $disk->url($path);
            }
        }

        return $urls;
    }

    public function emailFromName(): string
    {
        return (string) $this->settings->get('branding.email_from_name');
    }

    public function emailFromAddress(): string
    {
        return (string) $this->settings->get('branding.email_from_address');
    }
}
