<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use BackedEnum;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function getView(): string
    {
        return 'filament.pages.profile';
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.my_profile');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('resources.account_settings');
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->form->fill([
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
            'address' => $user->address ?? '',
            'license_number' => $user->license_number ?? '',
            'id_document_path' => $user->id_document_path ? [$user->id_document_path] : [],
            'license_document_path' => $user->license_document_path ? [$user->license_document_path] : [],
            'role' => $user->role?->value ?? 'renter',
            'status' => $user->status?->value ?? 'active',
            'is_verified' => $user->is_verified ?? false,
            'preferred_language' => app()->getLocale(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Personal Information Section
                Section::make(__('resources.personal_information'))
                    ->description(__('resources.personal_information_description'))
                    ->icon('heroicon-m-user')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('resources.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('resources.enter_full_name'))
                                    ->helperText(__('resources.name_helper')),

                                TextInput::make('email')
                                    ->label(__('resources.email'))
                                    ->email()
                                    ->required()
                                    ->unique(table: 'car_rental_users', ignorable: auth()->user())
                                    ->maxLength(255)
                                    ->placeholder(__('resources.email_placeholder'))
                                    ->suffixIcon('heroicon-m-envelope'),

                                TextInput::make('phone')
                                    ->label(__('resources.phone'))
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder(__('resources.phone_placeholder'))
                                    ->suffixIcon('heroicon-m-phone')
                                    ->helperText(__('resources.phone_helper')),

                                DatePicker::make('date_of_birth')
                                    ->label(__('resources.date_of_birth'))
                                    ->maxDate(now()->subYears(18))
                                    ->displayFormat('d/m/Y')
                                    ->placeholder(__('resources.date_of_birth_placeholder'))
                                    ->helperText(__('resources.age_requirement'))
                                    ->suffixIcon('heroicon-m-calendar'),

                                Select::make('preferred_language')
                                    ->label(__('resources.preferred_language'))
                                    ->options([
                                        'en' => __('resources.english'),
                                        'ar' => __('resources.arabic'),
                                    ])
                                    ->default('en')
                                    ->native(false)
                                    ->suffixIcon('heroicon-m-language'),

                                Select::make('role')
                                    ->label(__('resources.account_type'))
                                    ->options([
                                        'renter' => __('enums.user_role.renter'),
                                        'owner' => __('enums.user_role.owner'),
                                    ])
                                    ->default('owner')
                                    ->native(false)
                                    ->disabled()
                                    ->hidden()
                                    ->helperText(__('resources.role_helper')),
                            ]),

                        Textarea::make('address')
                            ->label(__('resources.address'))
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder(__('resources.address_placeholder'))
                            ->helperText(__('resources.address_helper'))
                            ->columnSpanFull(),
                    ]),

                // Driver License & Documents Section
                Section::make(__('resources.license_documents'))
                    ->description(__('resources.license_documents_description'))
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('license_number')
                                    ->label(__('resources.driver_license_number'))
                                    ->maxLength(50)
                                    ->placeholder(__('resources.license_number_placeholder'))
                                    ->suffixIcon('heroicon-m-credit-card')
                                    ->helperText(__('resources.license_required_helper')),

                                Toggle::make('is_verified')
                                    ->label(__('resources.account_verified'))
                                    ->disabled()
                                    ->helperText(__('resources.verification_admin_only')),
                            ]),

                        Grid::make()
                            ->schema([
                                FileUpload::make('id_document_path')
                                    ->label(__('resources.id_document'))
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(5120)
                                    ->directory('documents/ids')
                                    ->visibility('private')
                                    ->helperText(__('resources.id_document_helper'))
                                    ->image()
                                    ->imageResizeMode('contain')
                                    ->imageCropAspectRatio('3:2')
                                    ->imageResizeTargetWidth('800')
                                    ->imageResizeTargetHeight('600'),

                                FileUpload::make('license_document_path')
                                    ->label(__('resources.license_document'))
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(5120)
                                    ->directory('documents/licenses')
                                    ->visibility('private')
                                    ->helperText(__('resources.license_document_helper'))
                                    ->image()
                                    ->imageResizeMode('contain')
                                    ->imageCropAspectRatio('4:3')
                                    ->imageResizeTargetWidth('800')
                                    ->imageResizeTargetHeight('600'),
                            ]),
                    ]),

                // Account Security Section
                Section::make(__('resources.security_settings'))
                    ->description(__('resources.security_settings_description'))
                    ->icon('heroicon-m-lock-closed')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextInput::make('current_password')
                                    ->label(__('resources.current_password'))
                                    ->password()
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->rules(['current_password'])
                                    ->autocomplete('current-password')
                                    ->suffixIcon('heroicon-m-key')
                                    ->helperText(__('resources.current_password_helper')),
                            ]),

                        Grid::make()
                            ->schema([
                                TextInput::make('password')
                                    ->label(__('resources.new_password'))
                                    ->password()
                                    ->maxLength(255)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->rules([Password::default()])
                                    ->autocomplete('new-password')
                                    ->suffixIcon('heroicon-m-lock-closed')
                                    ->helperText(__('resources.password_requirements')),

                                TextInput::make('password_confirmation')
                                    ->label(__('resources.confirm_password'))
                                    ->password()
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->rules(['required_with:password', 'same:password'])
                                    ->autocomplete('new-password')
                                    ->suffixIcon('heroicon-m-lock-closed'),
                            ]),
                    ]),

                // Account Status Section (Information Only)
                Section::make(__('resources.account_status'))
                    ->description(__('resources.account_status_description'))
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('account_created')
                                    ->label(__('resources.member_since'))
                                    ->disabled()
                                    ->default(fn () => auth()->user()->created_at->format('d M Y'))
                                    ->suffixIcon('heroicon-m-calendar-days'),

                                TextInput::make('last_login_display')
                                    ->label(__('resources.last_login'))
                                    ->disabled()
                                    ->default(fn () => auth()->user()->last_login_at ?
                                        auth()->user()->last_login_at->format('d M Y H:i') :
                                        __('resources.never'))
                                    ->suffixIcon('heroicon-m-clock'),

                                TextInput::make('password_changed_display')
                                    ->label(__('resources.password_last_changed'))
                                    ->disabled()
                                    ->default(fn () => auth()->user()->password_changed_at ?
                                        auth()->user()->password_changed_at->format('d M Y') :
                                        __('resources.never'))
                                    ->suffixIcon('heroicon-m-shield-check'),
                            ]),
                    ]),
            ]);
    }

    public function updateProfile(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        // Remove password fields if not changing password
        if (empty($data['password'])) {
            unset($data['password'], $data['current_password'], $data['password_confirmation']);
        } else {
            // Hash the password if it's being changed
            $data['password'] = Hash::make($data['password']);
            $data['has_changed_default_password'] = true;
            $data['password_changed_at'] = now();
            unset($data['current_password'], $data['password_confirmation']);
        }

        // Remove preferred_language as it's not a user model field
        if (isset($data['preferred_language'])) {
            session(['locale' => $data['preferred_language']]);
            app()->setLocale($data['preferred_language']);
            unset($data['preferred_language']);
        }

        // Handle file upload arrays - FileUpload returns arrays, but we need strings for database
        if (isset($data['id_document_path']) && is_array($data['id_document_path'])) {
            $data['id_document_path'] = !empty($data['id_document_path']) ? $data['id_document_path'][0] : null;
        }
        
        if (isset($data['license_document_path']) && is_array($data['license_document_path'])) {
            $data['license_document_path'] = !empty($data['license_document_path']) ? $data['license_document_path'][0] : null;
        }

        // Remove display-only fields that aren't part of the user model
        unset($data['account_created'], $data['last_login_display'], $data['password_changed_display']);

        $user->update($data);

        Notification::make()
            ->title(__('resources.profile_updated_successfully'))
            ->success()
            ->send();

        // If password was changed, redirect to login
        if (isset($data['password'])) {
            Notification::make()
                ->title(__('resources.password_changed_login_required'))
                ->warning()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('updateProfile')
                ->label(__('resources.update_profile'))
                ->submit('updateProfile')
                ->color('primary'),
        ];
    }
}
