<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    /**
     * @throws Exception
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('quick_status_update')
                ->label('Quick Status Update')
                ->icon(Heroicon::OutlinedBolt)
                ->color(Color::Blue)
                ->form([
                    Select::make('status')
                        ->label('Update Status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'ongoing' => 'Ongoing',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default(fn () => $this->getRecord()->status)
                        ->required(),
                ])
                ->action(function (array $data, Booking $record) {
                    $record->update(['status' => $data['status']]);
                    Notification::make()
                        ->title('Status Updated')
                        ->body("Booking status changed to {$data['status']}")
                        ->success()
                        ->send();
                }),

            Action::make('calculate_total')
                ->label('Recalculate Total')
                ->icon(Heroicon::OutlinedCalculator)
                ->color(Color::Orange)
                ->visible(fn (Booking $record) => $record->vehicle && $record->start_date && $record->end_date)
                ->requiresConfirmation()
                ->modalHeading('Recalculate Total Amount')
                ->modalDescription('This will automatically calculate the total based on the vehicle daily rate and rental period.')
                ->action(function (Booking $record) {
                    if ($record->vehicle && $record->start_date && $record->end_date) {
                        $days = $record->start_date->diffInDays($record->end_date) + 1;
                        $newTotal = $days * $record->vehicle->daily_rate;
                        $record->update(['total_amount' => $newTotal]);

                        Notification::make()
                            ->title('Total Recalculated')
                            ->body('New total: $'.number_format($newTotal, 2))
                            ->success()
                            ->send();
                    }
                }),

            ViewAction::make()
                ->icon(Heroicon::OutlinedEye),

            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash)
                ->visible(fn (Booking $record) => in_array($record->status, ['cancelled', 'pending'])),

            ForceDeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),

            RestoreAction::make()
                ->icon(Heroicon::OutlinedArrowPath),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Booking Updated')
            ->body('The booking details have been successfully updated.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
