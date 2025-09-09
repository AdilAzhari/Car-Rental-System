<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label('Add Vehicle'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Vehicles')
                ->badge(fn () => $this->getResource()::getModel()::count()),

            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published'))
                ->badge(fn () => $this->getResource()::getModel()::where('status', 'published')->count())
                ->icon('heroicon-m-check-circle'),

            'available' => Tab::make('Available')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')->where('is_available', true))
                ->badge(fn () => $this->getResource()::getModel()::where('status', 'published')->where('is_available', true)->count())
                ->icon('heroicon-m-hand-thumb-up'),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => $this->getResource()::getModel()::where('status', 'draft')->count())
                ->icon('heroicon-m-document'),

            'maintenance' => Tab::make('Maintenance')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'maintenance'))
                ->badge(fn () => $this->getResource()::getModel()::where('status', 'maintenance')->count())
                ->icon('heroicon-m-wrench-screwdriver'),

            'luxury' => Tab::make('Luxury')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'luxury'))
                ->badge(fn () => $this->getResource()::getModel()::where('category', 'luxury')->count())
                ->icon('heroicon-m-star'),
        ];
    }
}