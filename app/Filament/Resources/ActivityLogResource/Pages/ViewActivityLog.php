<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

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
                                    ->label('Log ID')
                                    ->disabled(),

                                TextInput::make('log_name')
                                    ->label('Log Name')
                                    ->disabled(),

                                TextInput::make('event')
                                    ->label('Event Type')
                                    ->disabled(),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
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
                                    ->label('Subject Type')
                                    ->disabled(),

                                TextInput::make('subject_id')
                                    ->label('Subject ID')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Actor Information')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('causer_id')
                                    ->label('User ID')
                                    ->disabled(),

                                TextInput::make('created_at')
                                    ->label('Timestamp')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Activity Properties')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Textarea::make('properties')
                            ->label('Properties')
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'No properties'),
                    ])
                    ->collapsible(),
            ]);
    }
}