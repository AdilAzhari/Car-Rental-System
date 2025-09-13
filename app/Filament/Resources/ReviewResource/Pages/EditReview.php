<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('approve')
                ->label('Approve Review')
                ->icon('heroicon-m-check-circle')
                ->color(Color::Emerald)
                ->visible(fn (): bool => $this->record->status !== 'approved')
                ->requiresConfirmation()
                ->modalHeading('Approve Review')
                ->modalDescription('This will make the review visible to the public.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'approved',
                        'visibility' => 'public',
                    ]);

                    activity()
                        ->performedOn($this->record)
                        ->causedBy(auth()->user())
                        ->log('Review approved and published');

                    Notification::make()
                        ->success()
                        ->title('Review Approved')
                        ->body('The review has been approved and is now public.')
                        ->send();
                }),

            Action::make('reject')
                ->label('Reject Review')
                ->icon('heroicon-m-x-circle')
                ->color(Color::Red)
                ->visible(fn (): bool => $this->record->status !== 'rejected')
                ->requiresConfirmation()
                ->modalHeading('Reject Review')
                ->modalDescription('This will reject the review and hide it from public view.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'rejected',
                        'visibility' => 'hidden',
                    ]);

                    activity()
                        ->performedOn($this->record)
                        ->causedBy(auth()->user())
                        ->log('Review rejected');

                    Notification::make()
                        ->warning()
                        ->title('Review Rejected')
                        ->body('The review has been rejected.')
                        ->send();
                }),

            Action::make('flag')
                ->label('Flag for Review')
                ->icon('heroicon-m-flag')
                ->color(Color::Orange)
                ->visible(fn (): bool => $this->record->status !== 'flagged')
                ->requiresConfirmation()
                ->modalHeading('Flag Review')
                ->modalDescription('This will flag the review for further investigation.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'flagged',
                        'visibility' => 'private',
                    ]);

                    activity()
                        ->performedOn($this->record)
                        ->causedBy(auth()->user())
                        ->log('Review flagged for investigation');

                    Notification::make()
                        ->info()
                        ->title('Review Flagged')
                        ->body('The review has been flagged for investigation.')
                        ->send();
                }),

            Action::make('feature')
                ->label('Feature Review')
                ->icon('heroicon-m-star')
                ->color(Color::Yellow)
                ->visible(fn (): bool => $this->record->rating >= 4 && $this->record->status === 'approved')
                ->action(function (): void {
                    // TODO: Implement featured review functionality
                    Notification::make()
                        ->info()
                        ->title('Feature Coming Soon')
                        ->body('Featured reviews functionality will be available soon.')
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Review Updated')
            ->body('The review details have been updated successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterSave(): void
    {
        // Log the update activity
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->log('Review details updated');
    }
}
