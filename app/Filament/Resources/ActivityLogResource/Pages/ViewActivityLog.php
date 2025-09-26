<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    #[\Override]
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('resources.activity_overview'))
                    ->description(__('resources.activity_overview_description'))
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label(__('resources.log_id'))
                                    ->formatStateUsing(fn ($state): string => '#'.$state),

                                TextEntry::make('log_name')
                                    ->label(__('resources.log_name'))
                                    ->formatStateUsing(fn ($state): string|array|null => $state ? ucfirst((string) $state) : __('resources.system')),

                                TextEntry::make('event')
                                    ->label(__('resources.event_type'))
                                    ->formatStateUsing(fn ($state): string|array|null => $state ? __(ucfirst((string) $state)) : __('resources.unknown')),
                            ]),

                        TextEntry::make('description')
                            ->label(__('resources.description'))
                            ->formatStateUsing(fn ($state) => $state ?: __('resources.no_description_available'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('resources.subject_information'))
                    ->description(__('resources.subject_information_description'))
                    ->icon('heroicon-m-document')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('subject_type')
                                    ->label(__('resources.subject_type'))
                                    ->formatStateUsing(fn ($state): string|array|null => $state ? __(class_basename($state)) : __('resources.no_subject')),

                                TextEntry::make('subject_id')
                                    ->label(__('resources.subject_id'))
                                    ->formatStateUsing(fn ($state): string|array|null => $state ? '#'.$state : __('resources.no_id')),

                                TextEntry::make('subject_display')
                                    ->label(__('resources.subject_details'))
                                    ->formatStateUsing(function ($record) {
                                        if ($record->subject) {
                                            return match ($record->subject_type) {
                                                \App\Models\User::class => $record->subject->name ?? __('resources.deleted_user'),
                                                \App\Models\Vehicle::class => ($record->subject->make ?? '').' '.($record->subject->model ?? '').' ('.($record->subject->year ?? '').')',
                                                \App\Models\Booking::class => __('resources.booking').' #'.$record->subject->id,
                                                \App\Models\Review::class => __('resources.review').' #'.$record->subject->id,
                                                \App\Models\Payment::class => __('resources.payment').' #'.$record->subject->id,
                                                default => __('resources.unknown_subject')
                                            };
                                        }

                                        return __('resources.subject_deleted');
                                    }),
                            ]),
                    ]),

                Section::make(__('resources.actor_information'))
                    ->description(__('resources.actor_information_description'))
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('causer.name')
                                    ->label(__('resources.actor_name'))
                                    ->formatStateUsing(fn ($record) => $record->causer ? $record->causer->name : __('resources.system')),

                                TextEntry::make('causer.role')
                                    ->label(__('resources.actor_role'))
                                    ->formatStateUsing(fn ($record): string|array|null => $record->causer ? __(ucfirst((string) $record->causer->role)) : __('resources.system')),

                                TextEntry::make('created_at')
                                    ->label(__('resources.timestamp'))
                                    ->formatStateUsing(fn ($state): string|array|null => $state ? $state->format('Y-m-d H:i:s').' ('.$state->diffForHumans().')' : __('resources.unknown')),
                            ]),
                    ]),

                Section::make(__('resources.activity_properties'))
                    ->description(__('resources.activity_properties_description'))
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        TextEntry::make('properties')
                            ->label(__('resources.properties'))
                            ->columnSpanFull()
                            ->formatStateUsing(function ($record): string|array|null {
                                if (! $record->properties || $record->properties->isEmpty()) {
                                    return __('resources.no_properties_recorded');
                                }

                                // Convert the collection to array and format nicely
                                $properties = $record->properties->toArray();

                                return '<pre style="white-space: pre-wrap; font-family: monospace; font-size: 12px; background-color: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #e9ecef;">'.
                                       htmlspecialchars(json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).
                                       '</pre>';
                            })
                            ->html(),

                        TextEntry::make('batch_uuid')
                            ->label(__('resources.batch_uuid'))
                            ->formatStateUsing(fn ($state) => $state ?: __('resources.no_batch'))
                            ->visible(fn ($record): bool => ! empty($record->batch_uuid))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
