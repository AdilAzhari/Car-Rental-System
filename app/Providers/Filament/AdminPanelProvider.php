<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\LatestActivitiesWidget;
use App\Filament\Widgets\PopularVehiclesWidget;
use App\Filament\Widgets\RecentBookingsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\UserStatsWidget;
use App\Filament\Widgets\VehicleStatsWidget;
use App\Http\Middleware\LocalizationMiddleware;
use App\Http\Middleware\NewUserNotificationMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('SENTIENTS A.I')
//            ->brandLogo(asset('images/logo.jpg'))
            ->darkModeBrandLogo(asset('images/logo.jpg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/logo.jpg'))
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            // ->renderHook(
            //     'panels::body.start',
            //     fn (): string => view('filament.hooks.rtl-support')->render()
            // )
            // ->renderHook(
            //     'panels::topbar.end',
            //     fn (): string => view('filament.hooks.notification-bell')->render()
            // )
            // ->renderHook(
            //     'panels::head.end',
            //     fn (): string => '<link rel="stylesheet" href="'.asset('css/admin-fixes.css').'?v='.time().'">'
            // )
//             ->renderHook(
//                 'panels::user-menu.start',
//                 fn (): string => view('filament.hooks.user-menu')->render()
//             )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                DashboardStatsOverview::class,
                RevenueChartWidget::class,
                LatestActivitiesWidget::class,
                PopularVehiclesWidget::class,
                RecentBookingsWidget::class,
                VehicleStatsWidget::class,
                UserStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->resourceCreatePageRedirect('index')
            ->brandName('SENTIENTS A.I')
            ->authGuard('web')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
