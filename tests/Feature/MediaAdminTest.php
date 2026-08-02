<?php

use App\Enums\ContentType;
use App\Models\Media;
use App\Models\User;
use App\Services\ContentService;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('an editor can upload an image with its alt text', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/medias', [
        'file' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        'alt_fr' => 'Une équipe au travail',
    ])->assertRedirect('/medias');

    $media = Media::query()->firstOrFail();
    expect($media->alt_fr)->toBe('Une équipe au travail');
    Storage::disk('public')->assertExists($media->derivativePath(480));
});

test('non-image uploads are rejected', function () {
    $this->actingAs(User::factory()->admin()->create())->post('/medias', [
        'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});

test('alt texts are editable in both languages', function () {
    $admin = User::factory()->admin()->create();
    $media = app(MediaService::class)->store(UploadedFile::fake()->image('a.png', 100, 100), $admin);

    $this->actingAs($admin)->put('/medias/'.$media->id, [
        'alt_fr' => 'Bureau',
        'alt_en' => 'Office',
    ])->assertRedirect('/medias');

    $media->refresh();
    expect($media->alt_fr)->toBe('Bureau')
        ->and($media->alt_en)->toBe('Office')
        ->and($media->alt('en'))->toBe('Office');
});

test('deleting a used media is refused with an explanation', function () {
    $admin = User::factory()->admin()->create();
    $media = app(MediaService::class)->store(UploadedFile::fake()->image('used.png', 100, 100), $admin);
    app(ContentService::class)->set('accueil', 'hero.visuel', 'fr', ContentType::Image, ['media_id' => $media->id], $admin);

    $this->actingAs($admin)->delete('/medias/'.$media->id)->assertSessionHasErrors('media');

    expect(Media::query()->count())->toBe(1);
});

test('deleting an unused media works', function () {
    $admin = User::factory()->admin()->create();
    $media = app(MediaService::class)->store(UploadedFile::fake()->image('free.png', 100, 100), $admin);

    $this->actingAs($admin)->delete('/medias/'.$media->id)->assertRedirect('/medias');

    expect(Media::query()->count())->toBe(0);
});

test('members are locked out of the media library', function () {
    $member = User::factory()->create();

    $this->actingAs($member)->get('/medias')->assertForbidden();
    $this->actingAs($member)->post('/medias', [])->assertForbidden();
});
