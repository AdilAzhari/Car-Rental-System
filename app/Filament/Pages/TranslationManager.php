<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class TranslationManager extends Page
{
    protected static string|null|BackedEnum $navigationIcon = Heroicon::Language;

    #[Override]
    public function getView(): string
    {
        return 'filament.pages.translation-manager';
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('resources.system');
    }

    protected static ?int $navigationSort = 99;

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('Translation Manager');
    }

    #[Override]
    public function getTitle(): string
    {
        return __('Translation Manager');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role?->value === 'admin' || auth()->user()?->email === 'admin@example.com';
    }

    public function mount(): void
    {
        // Check if user has permission to access translation manager
        if (! static::canAccess()) {
            abort(403);
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role?->value === 'admin' || auth()->user()?->email === 'admin@example.com';
    }

    #[Override]
    protected function getViewData(): array
    {
        return [
            'translationManagerUrl' => url('/admin/translations'),
        ];
    }
}
