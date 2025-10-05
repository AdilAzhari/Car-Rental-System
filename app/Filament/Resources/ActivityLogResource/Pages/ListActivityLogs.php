<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Activities')
                ->badge(fn () => Activity::query()->count()),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->whereDate('created_at', today()))
                ->badge(fn () => Activity::query()->whereDate('created_at', today())->count())
                ->icon('heroicon-m-calendar-days'),

            'users' => Tab::make('User Activities')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('subject_type', User::class))
                ->badge(fn () => Activity::query()->where('subject_type', User::class)->count())
                ->icon('heroicon-m-user-group'),

            'vehicles' => Tab::make('Vehicle Activities')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('subject_type', Vehicle::class))
                ->badge(fn () => Activity::query()->where('subject_type', Vehicle::class)->count())
                ->icon('heroicon-m-truck'),

            'bookings' => Tab::make('Booking Activities')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('subject_type', Booking::class))
                ->badge(fn () => Activity::query()->where('subject_type', Booking::class)->count())
                ->icon('heroicon-m-calendar'),

            'auth' => Tab::make('Authentication')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->whereIn('description', ['User logged in', 'User logged out', 'Login attempt']))
                ->badge(fn () => Activity::query()->whereIn('description', ['User logged in', 'User logged out', 'Login attempt'])->count())
                ->icon('heroicon-m-key'),

            'errors' => Tab::make('Errors & Issues')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('log_name', 'error')
                    ->orWhere('description', 'like', '%error%')
                    ->orWhere('description', 'like', '%failed%'))
                ->badge(fn () => Activity::query()->where('log_name', 'error')
                    ->orWhere('description', 'like', '%error%')
                    ->orWhere('description', 'like', '%failed%')
                    ->count())
                ->icon('heroicon-m-exclamation-triangle'),

            'recent' => Tab::make('Recent (24h)')
                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('created_at', '>=', now()->subHours(24)))
                ->badge(fn () => Activity::query()->where('created_at', '>=', now()->subHours(24))->count())
                ->icon('heroicon-m-clock'),
        ];
    }

    #[\Override]
    protected function getHeaderWidgets(): array
    {
        return [
            // We can add activity statistics widgets here later
        ];
    }
}
