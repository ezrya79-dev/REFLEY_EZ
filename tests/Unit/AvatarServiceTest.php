<?php

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->service = new AvatarService;
});

test('stores a 256px square jpeg and links it to the user', function () {
    $user = User::factory()->create();

    $path = $this->service->store($user, UploadedFile::fake()->image('me.png', 640, 480));

    Storage::disk('public')->assertExists($path);
    expect($user->refresh()->avatar_path)->toBe($path);

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($width)->toBe(AvatarService::SIZE)->and($height)->toBe(AvatarService::SIZE);
});

test('replacing the photo removes the previous file', function () {
    $user = User::factory()->create();

    $first = $this->service->store($user, UploadedFile::fake()->image('a.jpg', 300, 300));
    $second = $this->service->store($user->refresh(), UploadedFile::fake()->image('b.jpg', 300, 300));

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

test('delete removes the file and clears the column', function () {
    $user = User::factory()->create();
    $path = $this->service->store($user, UploadedFile::fake()->image('a.jpg', 300, 300));

    $this->service->delete($user->refresh());

    Storage::disk('public')->assertMissing($path);
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('re-encoding strips exif metadata', function () {
    $user = User::factory()->create();

    $path = $this->service->store($user, UploadedFile::fake()->image('photo.jpg', 400, 400));

    expect(Storage::disk('public')->get($path))->not->toContain('Exif');
});
