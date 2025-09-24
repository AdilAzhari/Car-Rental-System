<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm_booking')
                ->label('Confirm Booking')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'pending_payment']) &&
                    auth()->user() && in_array(auth()->user()->role, ['admin', 'owner']))
                ->requiresConfirmation()
                ->modalHeading('Confirm Booking')
                ->modalDescription('Are you sure you want to confirm this booking?')
                ->modalSubmitActionLabel('Confirm Booking')
                ->form([
                    Textarea::make('notes')
                        ->label('Confirmation Notes')
                        ->placeholder('Add any notes about the booking confirmation...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'confirmed',
                    ]);

                    if (! empty($data['notes'])) {
                        $existingNotes = $this->record->special_requests;
                        $this->record->update([
                            'special_requests' => $existingNotes ? $existingNotes."\n\nAdmin Notes: ".$data['notes'] : 'Admin Notes: '.$data['notes'],
                        ]);
                    }

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon(Heroicon::OutlinedCreditCard)
                ->color('success')
                ->visible(fn (): bool => $this->record->payment_status === 'unpaid' &&
                    auth()->user() && auth()->user()->role == 'admin')
                ->requiresConfirmation()
                ->modalHeading('Mark Payment as Paid')
                ->modalDescription('Are you sure you want to mark this payment as paid?')
                ->modalSubmitActionLabel('Mark as Paid')
                ->form([
                    Textarea::make('payment_notes')
                        ->label('Payment Confirmation Notes')
                        ->placeholder('Add details about how payment was received...')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'payment_status' => 'paid',
                    ]);

                    if (! $this->record->payments()->exists()) {
                        $this->record->payments()->create([
                            'amount' => $this->record->total_amount,
                            'payment_method' => $this->record->payment_method ?: 'cash',
                            'payment_gateway' => 'manual',
                            'status' => 'paid',
                            'notes' => $data['payment_notes'],
                            'processed_at' => now(),
                        ]);
                    } else {
                        $payment = $this->record->payments()->latest()->first();
                        $payment->update([
                            'status' => 'paid',
                            'notes' => ($payment->notes ? $payment->notes."\n\n" : '').'Admin confirmed: '.$data['payment_notes'],
                            'processed_at' => now(),
                        ]);
                    }

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            Action::make('update_status')
                ->label('Update Status')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('info')
                ->form([
                    Select::make('status')
                        ->label('Booking Status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'ongoing' => 'Ongoing',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default($this->record->status)
                        ->required(),
                    Select::make('payment_status')
                        ->label('Payment Status')
                        ->options([
                            'unpaid' => 'Unpaid',
                            'paid' => 'Paid',
                            'refunded' => 'Refunded',
                        ])
                        ->default($this->record->payment_status)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => $data['status'],
                        'payment_status' => $data['payment_status'],
                    ]);

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
