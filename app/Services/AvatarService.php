<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Photo de profil : le client propose un recadrage (éditeur canvas), mais le
 * serveur ne s'y fie jamais — l'image est revalidée, ré-encodée (ce qui
 * supprime les métadonnées EXIF), recadrée au carré et fixée à 256 px.
 * L'ancien fichier est supprimé : un utilisateur = une photo.
 */
class AvatarService
{
    public const int SIZE = 256;

    public function store(User $user, UploadedFile $file): string
    {
        $image = imagecreatefromstring((string) file_get_contents((string) $file->getRealPath()));

        if ($image === false) {
            throw new \RuntimeException('Unreadable avatar upload.');
        }

        $square = $this->centerCrop($image);
        $resized = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagecopyresampled(
            $resized,
            $square,
            0,
            0,
            0,
            0,
            self::SIZE,
            self::SIZE,
            imagesx($square),
            imagesy($square),
        );

        ob_start();
        imagejpeg($resized, null, 88);
        $contents = (string) ob_get_clean();

        $previous = $user->avatar_path;
        $path = 'avatars/'.$user->id.'-'.str()->random(8).'.jpg';

        $disk = Storage::disk('public');
        $disk->put($path, $contents);

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous !== null && $previous !== $path) {
            $disk->delete($previous);
        }

        return $path;
    }

    public function delete(User $user): void
    {
        if ($user->avatar_path === null) {
            return;
        }

        Storage::disk('public')->delete($user->avatar_path);
        $user->forceFill(['avatar_path' => null])->save();
    }

    private function centerCrop(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);

        $cropped = imagecrop($image, [
            'x' => (int) (($width - $side) / 2),
            'y' => (int) (($height - $side) / 2),
            'width' => $side,
            'height' => $side,
        ]);

        return $cropped === false ? $image : $cropped;
    }
}
