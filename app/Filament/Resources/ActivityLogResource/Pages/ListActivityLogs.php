<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
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
                ->badge(fn () => Activity::count()),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => Activity::whereDate('created_at', today())->count())
                ->icon('heroicon-m-calendar-days'),

            'users' => Tab::make('User Activities')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subject_type', 'App\\Models\\User'))
                ->badge(fn () => Activity::where('subject_type', 'App\\Models\\User')->count())
                ->icon('heroicon-m-user-group'),

            'vehicles' => Tab::make('Vehicle Activities')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subject_type', 'App\\Models\\Vehicle'))
                ->badge(fn () => Activity::where('subject_type', 'App\\Models\\Vehicle')->count())
                ->icon('heroicon-m-truck'),

            'bookings' => Tab::make('Booking Activities')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('subject_type', 'App\\Models\\Booking'))
                ->badge(fn () => Activity::where('subject_type', 'App\\Models\\Booking')->count())
                ->icon('heroicon-m-calendar'),

            'auth' => Tab::make('Authentication')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('description', ['User logged in', 'User logged out', 'Login attempt']))
                ->badge(fn () => Activity::whereIn('description', ['User logged in', 'User logged out', 'Login attempt'])->count())
                ->icon('heroicon-m-key'),

            'errors' => Tab::make('Errors & Issues')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('log_name', 'error')
                    ->orWhere('description', 'like', '%error%')
                    ->orWhere('description', 'like', '%failed%'))
                ->badge(fn () => Activity::where('log_name', 'error')
                    ->orWhere('description', 'like', '%error%')
                    ->orWhere('description', 'like', '%failed%')
                    ->count())
                ->icon('heroicon-m-exclamation-triangle'),

            'recent' => Tab::make('Recent (24h)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subHours(24)))
                ->badge(fn () => Activity::where('created_at', '>=', now()->subHours(24))->count())
                ->icon('heroicon-m-clock'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // We can add activity statistics widgets here later
        ];
    }
}
