<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
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

    //    public function getTabs(): array
    //    {
    //        $modelClass = $this->getResource()::getModel();
    //
    //        // Calculate counts eagerly to avoid model context issues
    //        $allCount = $modelClass::count();
    //        $adminCount = $modelClass::where('role', UserRole::ADMIN)->count();
    //        $ownerCount = $modelClass::where('role', UserRole::OWNER)->count();
    //        $renterCount = $modelClass::where('role', UserRole::RENTER)->count();
    //        $verifiedCount = $modelClass::where('is_verified', true)->count();
    //        $unverifiedCount = $modelClass::where('is_verified', false)->count();
    //
    //        return [
    //            'all' => Tab::make('All Users')
    //                ->badge($allCount),
    //
    //            'admins' => Tab::make('Administrators')
    //                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('role', UserRole::ADMIN))
    //                ->badge($adminCount)
    //                ->icon('heroicon-m-shield-check'),
    //
    //            'owners' => Tab::make('Vehicle Owners')
    //                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('role', UserRole::OWNER))
    //                ->badge($ownerCount)
    //                ->icon('heroicon-m-building-storefront'),
    //
    //            'renters' => Tab::make('Customers')
    //                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('role', UserRole::RENTER))
    //                ->badge($renterCount)
    //                ->icon('heroicon-m-user'),
    //
    //            'verified' => Tab::make('Verified')
    //                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('is_verified', true))
    //                ->badge($verifiedCount)
    //                ->icon('heroicon-m-check-badge'),
    //
    //            'unverified' => Tab::make('Unverified')
    //                ->modifyQueryUsing(fn (Builder $builder) => $builder->where('is_verified', false))
    //                ->badge($unverifiedCount)
    //                ->icon('heroicon-m-exclamation-triangle'),
    //        ];
    //    }
}
