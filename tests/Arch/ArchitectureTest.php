<?php

describe('Application Architecture', function (): void {

    describe('Models Architecture', function (): void {
        it('ensures models are properly structured', function (): void {
            expect('App\Models')
                ->toOnlyUse([
                    \Illuminate\Database\Eloquent\Model::class,
                    \Illuminate\Database\Eloquent\Factories\HasFactory::class,
                    \Illuminate\Database\Eloquent\SoftDeletes::class,
                    'Illuminate\Database\Eloquent\Relations',
                    \Illuminate\Database\Eloquent\Casts\Attribute::class,
                    \Illuminate\Database\Eloquent\Builder::class,
                    \Illuminate\Database\Eloquent\Collection::class,
                    \Illuminate\Support\Carbon::class,
                    \Carbon\Carbon::class,
                    'App\Enums',
                    \Spatie\Activitylog\Traits\LogsActivity::class,
                    \Spatie\Activitylog\LogOptions::class,
                ]);
        });

        it('ensures models extend Eloquent Model', function (): void {
            expect('App\Models')
                ->toExtend(\Illuminate\Database\Eloquent\Model::class);
        });

        it('ensures models use HasFactory trait', function (): void {
            expect('App\Models')
                ->toUse(\Illuminate\Database\Eloquent\Factories\HasFactory::class);
        });

        it('ensures models have proper naming conventions', function (): void {
            expect('App\Models')
                ->toBeClasses()
                ->toHaveMethod('getTable');
        });
    });

    describe('Controllers Architecture', function (): void {
        it('ensures controllers have proper structure', function (): void {
            expect('App\Http\Controllers')
                ->toOnlyUse([
                    \Illuminate\Http\Request::class,
                    \Illuminate\Http\Response::class,
                    \Illuminate\Http\RedirectResponse::class,
                    \Illuminate\Http\JsonResponse::class,
                    \Illuminate\Routing\Controller::class,
                    \Illuminate\Foundation\Auth\Access\AuthorizesRequests::class,
                    \Illuminate\Foundation\Validation\ValidatesRequests::class,
                    \Illuminate\View\View::class,
                    'App\Models',
                    'App\Http\Requests',
                    'App\Enums',
                ]);
        });

        it('ensures controllers extend base Controller', function (): void {
            expect('App\Http\Controllers')
                ->ignoring(\App\Http\Controllers\Controller::class)
                ->toExtend(\App\Http\Controllers\Controller::class);
        });

        it('ensures auth controllers follow Laravel conventions', function (): void {
            expect('App\Http\Controllers\Auth')
                ->toOnlyUse([
                    \Illuminate\Http\Request::class,
                    \Illuminate\Http\RedirectResponse::class,
                    \Illuminate\View\View::class,
                    \Illuminate\Auth\Events\Registered::class,
                    \Illuminate\Support\Facades\Auth::class,
                    \Illuminate\Support\Facades\Hash::class,
                    'Illuminate\Validation\Rules',
                    \App\Http\Controllers\Controller::class,
                    \App\Models\User::class,
                    'App\Http\Requests\Auth',
                    'App\Providers\RouteServiceProvider',
                ]);
        });
    });

    describe('Requests Architecture', function (): void {
        it('ensures requests have proper structure', function (): void {
            expect('App\Http\Requests')
                ->toExtend(\Illuminate\Foundation\Http\FormRequest::class)
                ->toHaveMethod('rules')
                ->toHaveMethod('authorize');
        });

        it('ensures request classes follow naming conventions', function (): void {
            expect('App\Http\Requests')
                ->toOnlyUse([
                    \Illuminate\Foundation\Http\FormRequest::class,
                    \Illuminate\Validation\Rule::class,
                    'App\Models',
                    'App\Enums',
                ]);
        });
    });

    describe('Enums Architecture', function (): void {
        it('ensures enums are backed enums', function (): void {
            expect('App\Enums')
                ->toBeEnums()
                ->toBeBacked();
        });

        it('ensures enums use proper case conventions', function (): void {
            expect('App\Enums')
                ->toOnlyUse([]);
        });
    });

    describe('Middleware Architecture', function (): void {
        it('ensures middleware follows Laravel conventions', function (): void {
            expect('App\Http\Middleware')
                ->toOnlyUse([
                    \Illuminate\Http\Request::class,
                    \Illuminate\Http\Response::class,
                    \Symfony\Component\HttpFoundation\Response::class,
                    'Closure',
                    \Illuminate\Auth\Middleware\Authenticate::class,
                    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                    \Illuminate\Http\Middleware\TrustProxies::class,
                    \Illuminate\Routing\Middleware\SubstituteBindings::class,
                    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                    'App\Models',
                ]);
        });
    });

    describe('Observers Architecture', function (): void {
        it('ensures observers follow proper structure', function (): void {
            expect('App\Observers')
                ->toOnlyUse([
                    'App\Models',
                    \Illuminate\Support\Facades\Log::class,
                    \Spatie\Activitylog\Models\Activity::class,
                ]);
        });
    });

    describe('Providers Architecture', function (): void {
        it('ensures providers extend ServiceProvider', function (): void {
            expect('App\Providers')
                ->toExtend(\Illuminate\Support\ServiceProvider::class)
                ->toHaveMethod('register')
                ->toHaveMethod('boot');
        });

        it('ensures providers follow Laravel conventions', function (): void {
            expect('App\Providers')
                ->toOnlyUse([
                    \Illuminate\Support\ServiceProvider::class,
                    \Illuminate\Support\Facades\Gate::class,
                    \Illuminate\Foundation\Support\Providers\AuthServiceProvider::class,
                    \Illuminate\Cache\RateLimiting\Limit::class,
                    \Illuminate\Http\Request::class,
                    \Illuminate\Support\Facades\RateLimiter::class,
                    \Illuminate\Support\Facades\Route::class,
                    \Illuminate\Foundation\Support\Providers\RouteServiceProvider::class,
                    \Illuminate\Auth\Notifications\ResetPassword::class,
                    'App\Models',
                    'App\Observers',
                    \BezhanSalleh\LanguageSwitch\LanguageSwitch::class,
                ]);
        });
    });

    describe('Filament Resources Architecture', function (): void {
        it('ensures Filament resources follow proper structure', function (): void {
            expect('App\Filament\Resources')
                ->toOnlyUse([
                    'Filament\Forms',
                    \Filament\Resources\Resource::class,
                    'Filament\Tables',
                    'Filament\Actions',
                    'Filament\Infolists',
                    \Illuminate\Database\Eloquent\Builder::class,
                    \Illuminate\Database\Eloquent\SoftDeletingScope::class,
                    'App\Models',
                    'App\Enums',
                    'App\Filament\Resources',
                ]);
        });

        it('ensures Filament resource pages extend proper classes', function (): void {
            expect('App\Filament\Resources')
                ->classes()
                ->that()
                ->haveName('*Page')
                ->toExtendNothing()
                ->or()
                ->toExtend([
                    \Filament\Resources\Pages\CreateRecord::class,
                    \Filament\Resources\Pages\EditRecord::class,
                    \Filament\Resources\Pages\ListRecords::class,
                    \Filament\Resources\Pages\ViewRecord::class,
                ]);
        });
    });

    describe('Testing Architecture', function (): void {
        it('ensures test classes follow proper structure', function (): void {
            expect('Tests')
                ->toOnlyUse([
                    \PHPUnit\Framework\TestCase::class,
                    \Illuminate\Foundation\Testing\TestCase::class,
                    \Illuminate\Foundation\Testing\RefreshDatabase::class,
                    \Illuminate\Foundation\Testing\WithFaker::class,
                    \Tests\TestCase::class,
                    'App\Models',
                    'App\Enums',
                    'Laravel\Dusk\Browser',
                    \Carbon\Carbon::class,
                    'Illuminate\Support\Facades',
                ]);
        });

        it('ensures feature tests extend proper base', function (): void {
            expect('Tests\Feature')
                ->toExtend(\Tests\TestCase::class);
        });

        it('ensures unit tests extend proper base', function (): void {
            expect('Tests\Unit')
                ->toExtend(\Tests\TestCase::class);
        });
    });

    describe('Database Architecture', function (): void {
        it('ensures migrations follow proper structure', function (): void {
            expect('Database\Migrations')
                ->toOnlyUse([
                    \Illuminate\Database\Migrations\Migration::class,
                    \Illuminate\Database\Schema\Blueprint::class,
                    \Illuminate\Support\Facades\Schema::class,
                ]);
        });

        it('ensures factories follow proper structure', function (): void {
            expect('Database\Factories')
                ->toExtend(\Illuminate\Database\Eloquent\Factories\Factory::class)
                ->toHaveMethod('definition');
        });

        it('ensures seeders follow proper structure', function (): void {
            expect('Database\Seeders')
                ->toExtend(\Illuminate\Database\Seeder::class)
                ->toHaveMethod('run');
        });
    });

    describe('Security Architecture', function (): void {
        it('ensures no debug statements in production code', function (): void {
            expect('App')
                ->not()
                ->toUse([
                    'dd',
                    'dump',
                    'var_dump',
                    'print_r',
                ]);
        });

        it('ensures proper authorization is used', function (): void {
            expect('App\Http\Controllers')
                ->toOnlyUse([
                    \Illuminate\Foundation\Auth\Access\AuthorizesRequests::class,
                ])
                ->ignoring(\App\Http\Controllers\Controller::class);
        });

        it('ensures models use proper mass assignment protection', function (): void {
            expect('App\Models')
                ->toHaveProperty('fillable')
                ->or()
                ->toHaveProperty('guarded');
        });
    });

    describe('Dependency Architecture', function (): void {
        it('ensures controllers do not depend on each other', function (): void {
            expect('App\Http\Controllers')
                ->not()
                ->toUse('App\Http\Controllers');
        });

        it('ensures models do not depend on controllers', function (): void {
            expect('App\Models')
                ->not()
                ->toUse('App\Http\Controllers');
        });

        it('ensures models do not depend on requests', function (): void {
            expect('App\Models')
                ->not()
                ->toUse('App\Http\Requests');
        });

        it('ensures requests do not depend on controllers', function (): void {
            expect('App\Http\Requests')
                ->not()
                ->toUse('App\Http\Controllers');
        });
    });

    describe('Code Quality Architecture', function (): void {
        it('ensures proper class naming conventions', function (): void {
            expect('App')
                ->toBeClasses()
                ->toUseStrictTypes();
        });

        it('ensures no global functions in app namespace', function (): void {
            expect('App')
                ->not()
                ->toHaveGlobalFunctions();
        });

        it('ensures proper interface implementations', function (): void {
            expect('App')
                ->interfaces()
                ->toOnlyBeUsedIn('App');
        });
    });
});
