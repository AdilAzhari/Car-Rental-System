<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\KeyValueEntry;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Activity Overview')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Log ID')
                                    ->weight('bold'),

                                TextEntry::make('log_name')
                                    ->label('Log Name')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('event')
                                    ->label('Event Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        'viewed' => 'info',
                                        default => 'gray',
                                    }),
                            ]),

                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->size('lg')
                            ->weight('medium'),
                    ]),

                Section::make('Subject Information')
                    ->icon('heroicon-m-document')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('subject_type')
                                    ->label('Subject Type')
                                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'System')
                                    ->badge()
                                    ->color('secondary'),

                                TextEntry::make('subject_id')
                                    ->label('Subject ID')
                                    ->placeholder('N/A'),
                            ]),

                        TextEntry::make('subject')
                            ->label('Subject Details')
                            ->formatStateUsing(function ($record) {
                                if (!$record->subject) {
                                    return 'Subject no longer exists';
                                }

                                $subject = $record->subject;
                                
                                return match ($record->subject_type) {
                                    'App\\Models\\User' => "User: {$subject->name} ({$subject->email})",
                                    'App\\Models\\Vehicle' => "Vehicle: {$subject->make} {$subject->model} ({$subject->license_plate})",
                                    'App\\Models\\Booking' => "Booking #BK-{$subject->id} - {$subject->vehicle->make} {$subject->vehicle->model}",
                                    'App\\Models\\Review' => "Review #RV-{$subject->id} - {$subject->rating}/5 stars",
                                    'App\\Models\\Payment' => "Payment #{$subject->id} - " . ($subject->amount ?? 'N/A'),
                                    default => 'System Activity',
                                };
                            })
                            ->placeholder('N/A')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Actor Information')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('causer.name')
                                    ->label('Performed By')
                                    ->weight('medium')
                                    ->placeholder('System'),

                                TextEntry::make('causer.email')
                                    ->label('Email')
                                    ->copyable()
                                    ->placeholder('N/A'),

                                TextEntry::make('causer.role')
                                    ->label('Role')
                                    ->badge()
                                    ->colors([
                                        'danger' => 'admin',
                                        'warning' => 'owner',
                                        'success' => 'renter',
                                    ])
                                    ->placeholder('N/A'),

                                TextEntry::make('causer_id')
                                    ->label('User ID')
                                    ->placeholder('N/A'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Activity Properties')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        KeyValueEntry::make('properties.attributes')
                            ->label('New Attributes')
                            ->placeholder('No new attributes recorded')
                            ->columnSpanFull(),

                        KeyValueEntry::make('properties.old')
                            ->label('Old Attributes')
                            ->placeholder('No old attributes recorded')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => !empty($record->properties)),

                Section::make('Technical Details')
                    ->icon('heroicon-m-computer-desktop')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Timestamp')
                                    ->dateTime()
                                    ->since(),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime(),
                            ]),

                        KeyValueEntry::make('properties')
                            ->label('All Properties')
                            ->columnSpanFull()
                            ->visible(fn ($record) => !empty($record->properties)),
                    ])
                    ->collapsible(),

                Section::make('Related Activities')
                    ->icon('heroicon-m-link')
                    ->schema([
                        TextEntry::make('related_activities_count')
                            ->label('Related Activities')
                            ->state(function ($record) {
                                if (!$record->subject_id || !$record->subject_type) {
                                    return 'N/A';
                                }
                                
                                $count = \Spatie\Activitylog\Models\Activity::where('subject_type', $record->subject_type)
                                    ->where('subject_id', $record->subject_id)
                                    ->where('id', '!=', $record->id)
                                    ->count();
                                    
                                return $count . ' other activities';
                            })
                            ->badge()
                            ->color('info'),

                        TextEntry::make('user_activity_count')
                            ->label('User\'s Activities')
                            ->state(function ($record) {
                                if (!$record->causer_id) {
                                    return 'N/A';
                                }
                                
                                $count = \Spatie\Activitylog\Models\Activity::where('causer_id', $record->causer_id)
                                    ->where('id', '!=', $record->id)
                                    ->count();
                                    
                                return $count . ' other activities by this user';
                            })
                            ->badge()
                            ->color('warning'),
                    ])
                    ->collapsible(),
            ]);
    }
}