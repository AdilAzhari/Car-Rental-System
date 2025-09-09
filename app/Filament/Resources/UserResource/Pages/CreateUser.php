<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User Created Successfully')
            ->body('The user account has been created and is ready to use.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a default password if not provided
        if (!isset($data['password']) || empty($data['password'])) {
            $data['password'] = Hash::make('password123');
        }

        // Set default values
        $data['email_verified_at'] = $data['is_verified'] ? now() : null;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Send welcome email if user is verified
        if ($this->record->is_verified) {
            // TODO: Queue welcome email job
        }
    }
}