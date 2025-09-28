<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Override;
use Symfony\Component\HttpFoundation\Response;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    #[Override]
    public function mount(): void
    {
        // Check if user is admin before allowing access
        $user = Auth::user();
        if (! $user || $user->role !== UserRole::ADMIN) {
            abort(Response::HTTP_FORBIDDEN);
        }

        parent::mount();
    }
}
