<?php

use App\Models\Medium;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
    Storage::fake('public');
});

it('lets an admin upload media', function () {
    $file = UploadedFile::fake()->create('banner.jpg', 100, 'image/jpeg');

    $this->actingAs($this->admin)
        ->post(route('admin.media.store'), [
            'file' => $file,
            'alt' => 'A banner image',
        ])
        ->assertRedirect(route('admin.media.index'));

    Storage::disk('public')->assertExists('media/'.$file->hashName());

    expect(Medium::count())->toBe(1);

    $medium = Medium::first();
    expect($medium->filename)->toBe('banner.jpg')
        ->and($medium->mime)->toBe('image/jpeg')
        ->and($medium->alt)->toBe('A banner image')
        ->and($medium->uploaded_by)->toBe($this->admin->id);
});

it('rejects oversized uploads', function () {
    // Create a fake file larger than 10MB
    $file = UploadedFile::fake()->create('large.jpg', 20000, 'image/jpeg');

    $this->actingAs($this->admin)
        ->post(route('admin.media.store'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});

it('rejects wrong mime type uploads', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($this->admin)
        ->post(route('admin.media.store'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});

it('lets an admin list media', function () {
    Medium::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.media.index'))
        ->assertOk();
});

it('lets an admin delete media', function () {
    $medium = Medium::factory()->create();
    Storage::disk('public')->put($medium->path, 'fake content');

    $this->actingAs($this->admin)
        ->delete(route('admin.media.destroy', $medium))
        ->assertRedirect(route('admin.media.index'));

    Storage::disk('public')->assertMissing($medium->path);
    expect(Medium::find($medium->id))->toBeNull();
});

it('forbids non-admins from managing media', function () {
    $file = UploadedFile::fake()->create('x.jpg', 100, 'image/jpeg');

    $this->actingAs($this->user)
        ->post(route('admin.media.store'), ['file' => $file])
        ->assertForbidden();

    $medium = Medium::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('admin.media.destroy', $medium))
        ->assertForbidden();
});
