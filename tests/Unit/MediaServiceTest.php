<?php

use App\Models\Media;
use App\Models\User;
use App\Services\ContentService;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('public');
    $this->service = app(MediaService::class);
    $this->author = User::factory()->create();
});

test('storing an image derives every responsive width', function () {
    $media = $this->service->store(UploadedFile::fake()->image('photo.jpg', 2000, 1000), $this->author);

    Storage::disk('public')->assertExists($media->path);

    foreach (MediaService::DERIVATIVE_WIDTHS as $width) {
        Storage::disk('public')->assertExists($media->derivativePath($width));

        [$actualWidth] = getimagesizefromstring(Storage::disk('public')->get($media->derivativePath($width)));
        expect($actualWidth)->toBeLessThanOrEqual($width);
    }

    expect($media->width)->toBe(2000)->and($media->height)->toBe(1000);
});

test('identical uploads are deduplicated by checksum', function () {
    $file = UploadedFile::fake()->image('a.png', 100, 100);
    $contents = file_get_contents($file->getRealPath());

    $first = $this->service->store($file, $this->author);

    $copy = UploadedFile::fake()->createWithContent('b.png', $contents);
    $second = $this->service->store($copy, $this->author);

    expect($second->id)->toBe($first->id)
        ->and(Media::query()->count())->toBe(1);
});

test('a media referenced by a content block cannot be deleted', function () {
    $media = $this->service->store(UploadedFile::fake()->image('used.png', 100, 100), $this->author);

    app(ContentService::class)->set('accueil', 'hero.visuel', 'fr', App\Enums\ContentType::Image, ['media_id' => $media->id], $this->author);

    expect(fn () => $this->service->delete($media))->toThrow(ValidationException::class)
        ->and(Media::query()->count())->toBe(1);
});

test('deleting an unused media removes the files and the row', function () {
    $media = $this->service->store(UploadedFile::fake()->image('free.png', 100, 100), $this->author);
    $path = $media->path;

    $this->service->delete($media);

    Storage::disk('public')->assertMissing($path);
    expect(Media::query()->count())->toBe(0);
});

test('oversized dimensions are rejected before decoding', function () {
    $file = UploadedFile::fake()->image('huge.png', 9000, 100);

    $this->service->store($file, $this->author);
})->throws(ValidationException::class);
