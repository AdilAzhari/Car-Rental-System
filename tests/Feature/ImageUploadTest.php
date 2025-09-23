<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('can upload profile image', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('profile.jpg', 800, 600);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/upload/profile-image', [
            'image' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'image_path',
                'image_url',
            ],
        ]);

    $data = $response->json('data');
    Storage::disk('public')->assertExists("images/{$data['image_path']}");
});

it('can upload vehicle image', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('vehicle.jpg', 1200, 800);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/upload/vehicle-image', [
            'image' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'main_image_path',
                'thumbnail_path',
                'main_image_url',
                'thumbnail_url',
            ],
        ]);

    $data = $response->json('data');
    Storage::disk('public')->assertExists("images/{$data['main_image_path']}");
    Storage::disk('public')->assertExists("images/{$data['thumbnail_path']}");
});

it('validates profile image file size', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('large.jpg', 3000); // 3MB

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/upload/profile-image', [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('validates vehicle image file type', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 1000);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/upload/vehicle-image', [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

it('requires authentication for image upload', function () {
    $file = UploadedFile::fake()->image('profile.jpg');

    $response = $this->postJson('/api/upload/profile-image', [
        'image' => $file,
    ]);

    $response->assertUnauthorized();
});

it('can delete image', function () {
    $user = User::factory()->create();

    // First upload an image
    $file = UploadedFile::fake()->image('profile.jpg');
    $uploadResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/upload/profile-image', [
            'image' => $file,
        ]);

    $imagePath = $uploadResponse->json('data.image_path');

    // Then delete it
    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/upload/image', [
            'image_path' => $imagePath,
        ]);

    $response->assertSuccessful();
    Storage::disk('public')->assertMissing("images/{$imagePath}");
});
