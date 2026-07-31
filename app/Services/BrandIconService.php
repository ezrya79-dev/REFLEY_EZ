<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stocke le logo téléversé et en dérive toutes les icônes (favicons 16/32/48,
 * icône Apple 180, icônes PWA 192/512) au moment de l'upload, pour que le
 * <head> et le manifeste n'attendent jamais dix exports d'un designer.
 */
class BrandIconService
{
    /** @var array<int, int> */
    public const array SIZES = [16, 32, 48, 180, 192, 512];

    private const string DIRECTORY = 'branding';

    public static function iconPath(int $size): string
    {
        return self::DIRECTORY.'/icon-'.$size.'.png';
    }

    /** Remplace le logo (et ses dérivés) ; retourne le chemin stocké. */
    public function store(UploadedFile $file, ?string $previousPath): string
    {
        $disk = Storage::disk('public');

        $source = $this->readImage($file->getRealPath());

        $path = self::DIRECTORY.'/logo-'.str()->random(8).'.png';
        $disk->put($path, $this->encodePng($source));

        foreach (self::SIZES as $size) {
            $disk->put(self::iconPath($size), $this->encodePng($this->squareResize($source, $size)));
        }

        imagedestroy($source);

        if ($previousPath !== null && $previousPath !== $path) {
            $disk->delete($previousPath);
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        $disk = Storage::disk('public');

        if ($path !== null) {
            $disk->delete($path);
        }

        foreach (self::SIZES as $size) {
            $disk->delete(self::iconPath($size));
        }
    }

    private function readImage(string $realPath): \GdImage
    {
        $image = imagecreatefromstring((string) file_get_contents($realPath));

        if ($image === false) {
            throw new \RuntimeException('Unreadable image upload.');
        }

        imagesavealpha($image, true);

        return $image;
    }

    /** Redimensionne dans un carré transparent centré (sans déformer). */
    private function squareResize(\GdImage $source, int $size): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min($size / $width, $size / $height);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        imagecopyresampled(
            $canvas,
            $source,
            (int) (($size - $newWidth) / 2),
            (int) (($size - $newHeight) / 2),
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height,
        );

        return $canvas;
    }

    private function encodePng(\GdImage $image): string
    {
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
