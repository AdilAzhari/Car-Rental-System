<?php

use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('validates image upload service exists', function (): void {
    $imageUploadService = app(ImageUploadService::class);
    expect($imageUploadService)->toBeInstanceOf(ImageUploadService::class);
});

it('validates file upload request classes exist', function (): void {
    expect(class_exists(\App\Http\Requests\ProfileImageUploadRequest::class))->toBeTrue();
    expect(class_exists(\App\Http\Requests\VehicleImageUploadRequest::class))->toBeTrue();
});

it('validates image controller exists', function (): void {
    expect(class_exists(\App\Http\Controllers\ImageUploadController::class))->toBeTrue();
});

it('validates image configuration exists', function (): void {
    $config = config('image');
    expect($config)->toBeArray();
    expect($config)->toHaveKey('profile_images');
    expect($config)->toHaveKey('vehicle_images');
});
