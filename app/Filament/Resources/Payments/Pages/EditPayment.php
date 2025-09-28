<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    #[\Override]
    public function mount(int|string $record): void
    {
        // Check if user is admin before allowing access
        $user = Auth::user();
        if (! $user || $user->role !== UserRole::ADMIN) {
            abort(Response::HTTP_FORBIDDEN);
        }

        parent::mount($record);
    }
}
