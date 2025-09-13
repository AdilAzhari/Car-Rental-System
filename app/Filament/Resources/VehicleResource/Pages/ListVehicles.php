<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Enums\VehicleStatus;
use App\Filament\Resources\VehicleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label(__('resources.add_vehicle')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('resources.all_vehicles'))
                ->badge(fn () => $this->getResource()::getModel()::count()),

            'published' => Tab::make(__('resources.published'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', VehicleStatus::PUBLISHED))
                ->badge(fn () => $this->getResource()::getModel()::where('status', VehicleStatus::PUBLISHED)->count())
                ->icon('heroicon-m-check-circle'),

            'available' => Tab::make(__('resources.available'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', VehicleStatus::PUBLISHED)->where('is_available', true))
                ->badge(fn () => $this->getResource()::getModel()::where('status', VehicleStatus::PUBLISHED)->where('is_available', true)->count())
                ->icon('heroicon-m-hand-thumb-up'),

            'pending' => Tab::make(__('resources.pending'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', VehicleStatus::PENDING))
                ->badge(fn () => $this->getResource()::getModel()::where('status', VehicleStatus::PENDING)->count())
                ->icon('heroicon-m-clock'),

            'approved' => Tab::make(__('resources.approved'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', VehicleStatus::APPROVED))
                ->badge(fn () => $this->getResource()::getModel()::where('status', VehicleStatus::APPROVED)->count())
                ->icon('heroicon-m-check-badge'),

            'rejected' => Tab::make(__('resources.rejected'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', VehicleStatus::REJECTED))
                ->badge(fn () => $this->getResource()::getModel()::where('status', VehicleStatus::REJECTED)->count())
                ->icon('heroicon-m-x-circle'),

            'maintenance' => Tab::make(__('resources.maintenance'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', VehicleStatus::MAINTENANCE))
                ->badge(fn () => $this->getResource()::getModel()::where('status', VehicleStatus::MAINTENANCE)->count())
                ->icon('heroicon-m-wrench-screwdriver'),
        ];
    }
}
