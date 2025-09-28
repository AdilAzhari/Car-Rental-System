<?php

namespace App\Filament\Resources\Bookings;

use App\Enums\UserRole;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use App\Policies\BookingPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string $policy = BookingPolicy::class;

    public static function getNavigationLabel(): string
    {
        return __('resources.bookings');
    }

    public static function getModelLabel(): string
    {
        return __('resources.booking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.bookings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources.car_rental');
    }

    protected static ?int $navigationSort = 1;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::ArchiveBoxArrowDown;

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(auth()->user()->role === UserRole::RENTER, fn ($query) => $query->where('renter_id', auth()->id()))
            ->when(auth()->user()->role === UserRole::OWNER, fn ($query) => $query->whereHas('vehicle', function ($vehicleQuery): void {
                $vehicleQuery->where('owner_id', auth()->id());
            }));
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
