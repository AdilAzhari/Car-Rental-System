<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Filament Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Performance optimizations for Filament admin panel
    |
    */

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Widget Caching
        |--------------------------------------------------------------------------
        |
        | Cache widget data to improve dashboard performance
        |
        */
        'widgets' => [
            'enabled' => env('FILAMENT_CACHE_WIDGETS', true),
            'ttl' => env('FILAMENT_CACHE_WIDGETS_TTL', 300), // 5 minutes
            'store' => env('FILAMENT_CACHE_STORE', 'default'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Resource Caching
        |--------------------------------------------------------------------------
        |
        | Cache resource metadata and configurations
        |
        */
        'resources' => [
            'enabled' => env('FILAMENT_CACHE_RESOURCES', true),
            'ttl' => env('FILAMENT_CACHE_RESOURCES_TTL', 3600), // 1 hour
        ],

        /*
        |--------------------------------------------------------------------------
        | Navigation Caching
        |--------------------------------------------------------------------------
        |
        | Cache navigation items for faster menu rendering
        |
        */
        'navigation' => [
            'enabled' => env('FILAMENT_CACHE_NAVIGATION', true),
            'ttl' => env('FILAMENT_CACHE_NAVIGATION_TTL', 1800), // 30 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Optimizations
    |--------------------------------------------------------------------------
    |
    | Database query optimizations
    |
    */
    'database' => [
        'eager_loading' => [
            'enabled' => true,
            'relations' => [
                // Define commonly loaded relations
                'bookings' => ['renter', 'vehicle', 'payments'],
                'vehicles' => ['owner', 'bookings'],
                'users' => ['bookings', 'vehicles'],
                'payments' => ['booking', 'booking.vehicle', 'booking.renter'],
            ],
        ],

        'pagination' => [
            'per_page' => env('FILAMENT_PAGINATION_PER_PAGE', 25),
            'simple_pagination' => env('FILAMENT_SIMPLE_PAGINATION', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Optimizations
    |--------------------------------------------------------------------------
    |
    | Frontend asset optimizations
    |
    */
    'assets' => [
        'preload_fonts' => true,
        'lazy_load_images' => true,
        'compress_css' => env('APP_ENV') === 'production',
        'compress_js' => env('APP_ENV') === 'production',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Optimizations
    |--------------------------------------------------------------------------
    |
    | Table rendering optimizations
    |
    */
    'tables' => [
        'defer_loading' => true,
        'sticky_header' => true,
        'virtualization' => [
            'enabled' => env('FILAMENT_TABLE_VIRTUALIZATION', false),
            'threshold' => 100, // Enable for tables with more than 100 rows
        ],
    ],
];
