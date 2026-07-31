<?php

use App\Services\BrandIconService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->service = new BrandIconService;
});

test('storing a logo derives every favicon and pwa icon size', function () {
    $path = $this->service->store(UploadedFile::fake()->image('logo.png', 1024, 512), null);

    Storage::disk('public')->assertExists($path);

    foreach (BrandIconService::SIZES as $size) {
        $iconPath = BrandIconService::iconPath($size);
        Storage::disk('public')->assertExists($iconPath);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($iconPath));
        expect($width)->toBe($size)->and($height)->toBe($size);
    }
});

test('replacing the logo removes the previous file', function () {
    $first = $this->service->store(UploadedFile::fake()->image('a.png', 200, 200), null);
    $second = $this->service->store(UploadedFile::fake()->image('b.png', 200, 200), $first);

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

test('delete removes the logo and derived icons', function () {
    $path = $this->service->store(UploadedFile::fake()->image('a.png', 200, 200), null);

    $this->service->delete($path);

    Storage::disk('public')->assertMissing($path);

    foreach (BrandIconService::SIZES as $size) {
        Storage::disk('public')->assertMissing(BrandIconService::iconPath($size));
    }
});
