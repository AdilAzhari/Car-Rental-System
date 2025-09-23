<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for configuring image upload limits and resizing options
    | for different types of images in the application.
    |
    */

    'max_file_size' => 5120, // 5MB in kilobytes

    'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],

    'profile_images' => [
        'max_width' => 400,
        'max_height' => 400,
        'quality' => 85,
        'max_file_size' => 2048, // 2MB
    ],

    'vehicle_images' => [
        'max_width' => 1200,
        'max_height' => 800,
        'quality' => 90,
        'max_file_size' => 5120, // 5MB
        'thumbnail' => [
            'width' => 300,
            'height' => 200,
        ],
    ],

    'storage_path' => 'public/images',
];
