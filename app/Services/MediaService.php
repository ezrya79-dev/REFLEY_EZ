<?php

namespace App\Services;

use App\Models\ContentBlock;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Bibliothèque d'images du site. Chaque upload est ré-encodé (les
 * métadonnées EXIF et charges utiles disparaissent) et ses dérivés
 * responsive sont générés immédiatement — jamais de redimensionnement
 * pendant une requête publique. Les uploads identiques sont dédupliqués
 * par empreinte.
 */
class MediaService
{
    /** @var array<int, int> */
    public const array DERIVATIVE_WIDTHS = [480, 960, 1600];

    private const int MAX_DIMENSION = 8000;

    public function store(UploadedFile $file, User $author): Media
    {
        $realPath = (string) $file->getRealPath();

        // Garde anti « decompression bomb » : on lit les dimensions déclarées
        // avant de décoder quoi que ce soit avec GD.
        $info = getimagesize($realPath);

        if ($info === false || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw ValidationException::withMessages(['file' => __('media.errorUnreadable')]);
        }

        $checksum = hash_file('sha256', $realPath);

        $existing = Media::query()->where('checksum', $checksum)->first();

        if ($existing !== null) {
            return $existing;
        }

        $image = imagecreatefromstring((string) file_get_contents($realPath));

        if ($image === false) {
            throw ValidationException::withMessages(['file' => __('media.errorUnreadable')]);
        }

        imagesavealpha($image, true);
        $width = imagesx($image);
        $height = imagesy($image);

        $disk = Storage::disk('public');

        ob_start();
        imagepng($image);
        $original = (string) ob_get_clean();

        $media = Media::query()->create([
            'path' => 'media/original-'.str()->random(10).'.png',
            'width' => $width,
            'height' => $height,
            'size_bytes' => strlen($original),
            'checksum' => $checksum,
            'uploaded_by' => $author->id,
        ]);

        $disk->put($media->path, $original);

        foreach (self::DERIVATIVE_WIDTHS as $targetWidth) {
            $disk->put($media->derivativePath($targetWidth), $this->encodeWebp($image, $targetWidth));
        }

        imagedestroy($image);

        return $media;
    }

    /**
     * @throws ValidationException si le média est encore référencé.
     */
    public function delete(Media $media): void
    {
        if ($this->usages($media)->isNotEmpty()) {
            throw ValidationException::withMessages(['media' => __('media.errorInUse')]);
        }

        $disk = Storage::disk('public');
        $disk->delete($media->path);

        foreach (self::DERIVATIVE_WIDTHS as $width) {
            $disk->delete($media->derivativePath($width));
        }

        $media->delete();
    }

    /**
     * Blocs de contenu qui référencent ce média.
     *
     * @return Collection<int, ContentBlock>
     */
    public function usages(Media $media): Collection
    {
        return ContentBlock::query()
            ->where('type', 'image')
            ->get()
            ->filter(function (ContentBlock $block) use ($media): bool {
                $value = json_decode($block->value, true);

                return is_array($value) && ($value['media_id'] ?? null) === $media->id;
            })
            ->values();
    }

    /** Redimensionne à la largeur cible (jamais agrandi) et encode en WebP. */
    private function encodeWebp(\GdImage $image, int $targetWidth): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $targetWidth / $width);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagewebp($resized, null, 82);

        return (string) ob_get_clean();
    }
}
