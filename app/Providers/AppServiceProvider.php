<?php

namespace App\Providers;

use App\Models\Vehicle;
use App\Observers\VehicleObserver;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token): string => config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}");

        // Vehicle::observe(VehicleObserver::class);

        // Configure Language Switch
        // LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
        //     $switch
        //         ->locales(['ar', 'en'])
        //         ->labels([
        //             'ar' => 'العربية',
        //             'en' => 'English',
        //         ])
        //         ->flags([
        //             'ar' => asset('images/flags/sa.svg'),
        //             'en' => asset('images/flags/us.svg'),
        //         ])
        //         ->displayLocale('name')
        //         ->circular();
        // });
    }
}
