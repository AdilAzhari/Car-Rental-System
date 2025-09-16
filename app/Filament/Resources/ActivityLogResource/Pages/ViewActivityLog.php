<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

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
                                TextInput::make('id')
                                    ->label(__('resources.log_id'))
                                    ->formatStateUsing(fn ($state) => '#' . $state)
                                    ->disabled(),

                                TextInput::make('log_name')
                                    ->label(__('resources.log_name'))
                                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : __('resources.system'))
                                    ->disabled(),

                                TextInput::make('event')
                                    ->label(__('resources.event_type'))
                                    ->formatStateUsing(fn ($state) => $state ? __(ucfirst($state)) : __('resources.unknown'))
                                    ->disabled(),
                            ]),

                        Textarea::make('description')
                            ->label(__('resources.description'))
                            ->formatStateUsing(fn ($state) => $state ?: __('resources.no_description_available'))
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('resources.subject_information'))
                    ->description(__('resources.subject_information_description'))
                    ->icon('heroicon-m-document')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('subject_type')
                                    ->label(__('resources.subject_type'))
                                    ->formatStateUsing(fn ($state) => $state ? __(class_basename($state)) : __('resources.no_subject'))
                                    ->disabled(),

                                TextInput::make('subject_id')
                                    ->label(__('resources.subject_id'))
                                    ->formatStateUsing(fn ($state) => $state ? '#' . $state : __('resources.no_id'))
                                    ->disabled(),

                                TextInput::make('subject_display')
                                    ->label(__('resources.subject_details'))
                                    ->formatStateUsing(function ($record) {
                                        if ($record->subject) {
                                            return match ($record->subject_type) {
                                                'App\\Models\\User' => $record->subject->name ?? __('resources.deleted_user'),
                                                'App\\Models\\Vehicle' => ($record->subject->make ?? '') . ' ' . ($record->subject->model ?? '') . ' (' . ($record->subject->year ?? '') . ')',
                                                'App\\Models\\Booking' => __('resources.booking') . ' #' . $record->subject->id,
                                                'App\\Models\\Review' => __('resources.review') . ' #' . $record->subject->id,
                                                'App\\Models\\Payment' => __('resources.payment') . ' #' . $record->subject->id,
                                                default => __('resources.unknown_subject')
                                            };
                                        }
                                        return __('resources.subject_deleted');
                                    })
                                    ->disabled(),
                            ]),
                    ]),

                Section::make(__('resources.actor_information'))
                    ->description(__('resources.actor_information_description'))
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('causer.name')
                                    ->label(__('resources.actor_name'))
                                    ->formatStateUsing(fn ($record) => $record->causer ? $record->causer->name : __('resources.system'))
                                    ->disabled(),

                                TextInput::make('causer.role')
                                    ->label(__('resources.actor_role'))
                                    ->formatStateUsing(fn ($record) => $record->causer ? __(ucfirst($record->causer->role)) : __('resources.system'))
                                    ->disabled(),

                                TextInput::make('created_at')
                                    ->label(__('resources.timestamp'))
                                    ->formatStateUsing(fn ($state) => $state ? $state->format('Y-m-d H:i:s') . ' (' . $state->diffForHumans() . ')' : __('resources.unknown'))
                                    ->disabled(),
                            ]),
                    ]),

                Section::make(__('resources.activity_properties'))
                    ->description(__('resources.activity_properties_description'))
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Textarea::make('properties')
                            ->label(__('resources.properties'))
                            ->disabled()
                            ->rows(6)
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state) {
                                if (!$state || empty($state)) {
                                    return __('resources.no_properties_recorded');
                                }

                                if (is_array($state)) {
                                    return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }

                                return $state;
                            }),

                        Textarea::make('batch_uuid')
                            ->label(__('resources.batch_uuid'))
                            ->formatStateUsing(fn ($state) => $state ?: __('resources.no_batch'))
                            ->disabled()
                            ->rows(1)
                            ->visible(fn ($record) => !empty($record->batch_uuid))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
