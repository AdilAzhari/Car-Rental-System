<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Activity Overview')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('id')
                                    ->label(__('resources.log_id'))
                                    ->disabled(),

                                TextInput::make('log_name')
                                    ->label(__('resources.log_name'))
                                    ->disabled(),

                                TextInput::make('event')
                                    ->label(__('resources.event_type'))
                                    ->disabled(),
                            ]),

                        Textarea::make('description')
                            ->label(__('resources.description'))
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Subject Information')
                    ->icon('heroicon-m-document')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('subject_type')
                                    ->label(__('resources.subject_type'))
                                    ->disabled(),

                                TextInput::make('subject_id')
                                    ->label(__('resources.subject_id'))
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Actor Information')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('causer_id')
                                    ->label(__('resources.user_id'))
                                    ->disabled(),

                                TextInput::make('created_at')
                                    ->label(__('resources.timestamp'))
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Activity Properties')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Textarea::make('properties')
                            ->label(__('resources.properties'))
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'No properties'),
                    ])
                    ->collapsible(),
            ]);
    }
}
