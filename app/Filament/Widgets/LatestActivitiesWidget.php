<?php

namespace App\Filament\Widgets;

use Spatie\Activitylog\Models\Activity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestActivitiesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Activity')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\BadgeColumn::make('event')
                    ->label('Event')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                        'info' => 'viewed',
                        'primary' => fn ($state) => in_array($state, ['logged_in', 'logged_out']),
                        'gray' => 'default',
                    ]),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : 'System')
                    ->badge()
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('causer.role')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'owner',
                        'success' => 'renter',
                    ])
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Activity $record): string => 
                        route('filament.admin.resources.activity-logs.view', $record)
                    ),
            ])
            ->poll('30s')
            ->heading('Latest System Activities')
            ->description('Real-time activity feed from across the platform');
    }
}