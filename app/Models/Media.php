<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $path
 * @property string|null $alt_fr
 * @property string|null $alt_en
 * @property int $width
 * @property int $height
 * @property int $size_bytes
 * @property string $checksum
 * @property int|null $uploaded_by
 */
#[Fillable(['path', 'alt_fr', 'alt_en', 'width', 'height', 'size_bytes', 'checksum', 'uploaded_by'])]
class Media extends Model
{
    protected $table = 'media';

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /** URL du dérivé WebP le plus proche (480/960/1600), pour srcset. */
    public function derivativeUrl(int $width): string
    {
        return Storage::disk('public')->url($this->derivativePath($width));
    }

    public function derivativePath(int $width): string
    {
        return 'media/'.$this->id.'/w'.$width.'.webp';
    }

    /** Texte alternatif localisé, avec repli FR. */
    public function alt(string $locale): string
    {
        $alt = $locale === 'en' ? ($this->alt_en ?? $this->alt_fr) : $this->alt_fr;

        return $alt ?? '';
    }
}
