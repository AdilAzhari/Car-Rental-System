<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('Add User'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users')
                ->badge(fn () => $this->getResource()::getModel()::count()),

            'admins' => Tab::make('Administrators')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'admin'))
                ->badge(fn () => $this->getResource()::getModel()::where('role', 'admin')->count())
                ->icon('heroicon-m-shield-check'),

            'owners' => Tab::make('Vehicle Owners')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'owner'))
                ->badge(fn () => $this->getResource()::getModel()::where('role', 'owner')->count())
                ->icon('heroicon-m-building-storefront'),

            'renters' => Tab::make('Customers')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'renter'))
                ->badge(fn () => $this->getResource()::getModel()::where('role', 'renter')->count())
                ->icon('heroicon-m-user'),

            'verified' => Tab::make('Verified')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', true))
                ->badge(fn () => $this->getResource()::getModel()::where('is_verified', true)->count())
                ->icon('heroicon-m-check-badge'),

            'unverified' => Tab::make('Unverified')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', false))
                ->badge(fn () => $this->getResource()::getModel()::where('is_verified', false)->count())
                ->icon('heroicon-m-exclamation-triangle'),
        ];
    }
}