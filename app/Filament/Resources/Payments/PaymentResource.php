<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getNavigationGroup(): ?string
    {
        return __('resources.transactions');
    }

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Scope based on user role
        $user = Auth::user();
        if (! $user) {
            return $query->whereRaw('1 = 0'); // Return empty results if not authenticated
        }

        if ($user->role === 'admin') {
            // Admin can see all payments
            return $query;
        } elseif ($user->role === 'owner') {
            // Owner can see payments for their vehicles
            return $query->whereHas('booking.vehicle', function ($q) use ($user): void {
                $q->where('owner_id', $user->id);
            });
        } else {
            // Renter can see their own payments
            return $query->whereHas('booking', function ($q) use ($user): void {
                $q->where('renter_id', $user->id);
            });
        }
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user && $user->role == 'admin';
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        $pages = [
            'index' => ListPayments::route('/'),
        ];

        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            $pages['create'] = CreatePayment::route('/create');
            $pages['edit'] = EditPayment::route('/{record}/edit');
        }

        return $pages;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->role === 'admin') {
            return static::getModel()::where('payment_status', 'pending')->count();
        } elseif ($user->role === 'owner') {
            return static::getModel()::whereHas('booking.vehicle', fn ($q) => $q->where('owner_id', $user->id))
                ->where('payment_status', 'pending')->count();
        } else {
            return static::getModel()::whereHas('booking', fn ($q) => $q->where('renter_id', $user->id))
                ->where('payment_status', 'pending')->count();
        }
    }
}
