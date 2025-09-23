<?php

use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('validates image upload service exists', function () {
    $service = app(ImageUploadService::class);
    expect($service)->toBeInstanceOf(ImageUploadService::class);
});

it('validates file upload request classes exist', function () {
    expect(class_exists('App\Http\Requests\ProfileImageUploadRequest'))->toBeTrue();
    expect(class_exists('App\Http\Requests\VehicleImageUploadRequest'))->toBeTrue();
});

it('validates image controller exists', function () {
    expect(class_exists('App\Http\Controllers\ImageUploadController'))->toBeTrue();
});

it('validates image configuration exists', function () {
    $config = config('image');
    expect($config)->toBeArray();
    expect($config)->toHaveKey('profile_images');
    expect($config)->toHaveKey('vehicle_images');
});
