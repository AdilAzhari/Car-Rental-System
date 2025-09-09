<?php

use Pest\Arch\Expectations\Archable;

describe('Application Architecture', function () {
    
    describe('Models Architecture', function () {
        it('ensures models are properly structured', function () {
            expect('App\Models')
                ->toOnlyUse([
                    'Illuminate\Database\Eloquent\Model',
                    'Illuminate\Database\Eloquent\Factories\HasFactory',
                    'Illuminate\Database\Eloquent\SoftDeletes',
                    'Illuminate\Database\Eloquent\Relations',
                    'Illuminate\Database\Eloquent\Casts\Attribute',
                    'Illuminate\Database\Eloquent\Builder',
                    'Illuminate\Database\Eloquent\Collection',
                    'Illuminate\Support\Carbon',
                    'Carbon\Carbon',
                    'App\Enums',
                    'Spatie\Activitylog\Traits\LogsActivity',
                    'Spatie\Activitylog\LogOptions',
                ]);
        });

        it('ensures models extend Eloquent Model', function () {
            expect('App\Models')
                ->toExtend('Illuminate\Database\Eloquent\Model');
        });

        it('ensures models use HasFactory trait', function () {
            expect('App\Models')
                ->toUse('Illuminate\Database\Eloquent\Factories\HasFactory');
        });

        it('ensures models have proper naming conventions', function () {
            expect('App\Models')
                ->toBeClasses()
                ->toHaveMethod('getTable');
        });
    });

    describe('Controllers Architecture', function () {
        it('ensures controllers have proper structure', function () {
            expect('App\Http\Controllers')
                ->toOnlyUse([
                    'Illuminate\Http\Request',
                    'Illuminate\Http\Response',
                    'Illuminate\Http\RedirectResponse',
                    'Illuminate\Http\JsonResponse',
                    'Illuminate\Routing\Controller',
                    'Illuminate\Foundation\Auth\Access\AuthorizesRequests',
                    'Illuminate\Foundation\Validation\ValidatesRequests',
                    'Illuminate\View\View',
                    'App\Models',
                    'App\Http\Requests',
                    'App\Enums',
                ]);
        });

        it('ensures controllers extend base Controller', function () {
            expect('App\Http\Controllers')
                ->ignoring('App\Http\Controllers\Controller')
                ->toExtend('App\Http\Controllers\Controller');
        });

        it('ensures auth controllers follow Laravel conventions', function () {
            expect('App\Http\Controllers\Auth')
                ->toOnlyUse([
                    'Illuminate\Http\Request',
                    'Illuminate\Http\RedirectResponse',
                    'Illuminate\View\View',
                    'Illuminate\Auth\Events\Registered',
                    'Illuminate\Support\Facades\Auth',
                    'Illuminate\Support\Facades\Hash',
                    'Illuminate\Validation\Rules',
                    'App\Http\Controllers\Controller',
                    'App\Models\User',
                    'App\Http\Requests\Auth',
                    'App\Providers\RouteServiceProvider',
                ]);
        });
    });

    describe('Requests Architecture', function () {
        it('ensures requests have proper structure', function () {
            expect('App\Http\Requests')
                ->toExtend('Illuminate\Foundation\Http\FormRequest')
                ->toHaveMethod('rules')
                ->toHaveMethod('authorize');
        });

        it('ensures request classes follow naming conventions', function () {
            expect('App\Http\Requests')
                ->toOnlyUse([
                    'Illuminate\Foundation\Http\FormRequest',
                    'Illuminate\Validation\Rule',
                    'App\Models',
                    'App\Enums',
                ]);
        });
    });

    describe('Enums Architecture', function () {
        it('ensures enums are backed enums', function () {
            expect('App\Enums')
                ->toBeEnums()
                ->toBeBacked();
        });

        it('ensures enums use proper case conventions', function () {
            expect('App\Enums')
                ->toOnlyUse([]);
        });
    });

    describe('Middleware Architecture', function () {
        it('ensures middleware follows Laravel conventions', function () {
            expect('App\Http\Middleware')
                ->toOnlyUse([
                    'Illuminate\Http\Request',
                    'Illuminate\Http\Response',
                    'Symfony\Component\HttpFoundation\Response',
                    'Closure',
                    'Illuminate\Auth\Middleware\Authenticate',
                    'Illuminate\Foundation\Http\Middleware\VerifyCsrfToken',
                    'Illuminate\Http\Middleware\TrustProxies',
                    'Illuminate\Routing\Middleware\SubstituteBindings',
                    'Illuminate\View\Middleware\ShareErrorsFromSession',
                    'App\Models',
                ]);
        });
    });

    describe('Observers Architecture', function () {
        it('ensures observers follow proper structure', function () {
            expect('App\Observers')
                ->toOnlyUse([
                    'App\Models',
                    'Illuminate\Support\Facades\Log',
                    'Spatie\Activitylog\Models\Activity',
                ]);
        });
    });

    describe('Providers Architecture', function () {
        it('ensures providers extend ServiceProvider', function () {
            expect('App\Providers')
                ->toExtend('Illuminate\Support\ServiceProvider')
                ->toHaveMethod('register')
                ->toHaveMethod('boot');
        });

        it('ensures providers follow Laravel conventions', function () {
            expect('App\Providers')
                ->toOnlyUse([
                    'Illuminate\Support\ServiceProvider',
                    'Illuminate\Support\Facades\Gate',
                    'Illuminate\Foundation\Support\Providers\AuthServiceProvider',
                    'Illuminate\Cache\RateLimiting\Limit',
                    'Illuminate\Http\Request',
                    'Illuminate\Support\Facades\RateLimiter',
                    'Illuminate\Support\Facades\Route',
                    'Illuminate\Foundation\Support\Providers\RouteServiceProvider',
                    'Illuminate\Auth\Notifications\ResetPassword',
                    'App\Models',
                    'App\Observers',
                    'BezhanSalleh\LanguageSwitch\LanguageSwitch',
                ]);
        });
    });

    describe('Filament Resources Architecture', function () {
        it('ensures Filament resources follow proper structure', function () {
            expect('App\Filament\Resources')
                ->toOnlyUse([
                    'Filament\Forms',
                    'Filament\Resources\Resource',
                    'Filament\Tables',
                    'Filament\Actions',
                    'Filament\Infolists',
                    'Illuminate\Database\Eloquent\Builder',
                    'Illuminate\Database\Eloquent\SoftDeletingScope',
                    'App\Models',
                    'App\Enums',
                    'App\Filament\Resources',
                ]);
        });

        it('ensures Filament resource pages extend proper classes', function () {
            expect('App\Filament\Resources')
                ->classes()
                ->that()
                ->haveName('*Page')
                ->toExtendNothing()
                ->or()
                ->toExtend([
                    'Filament\Resources\Pages\CreateRecord',
                    'Filament\Resources\Pages\EditRecord',
                    'Filament\Resources\Pages\ListRecords',
                    'Filament\Resources\Pages\ViewRecord',
                ]);
        });
    });

    describe('Testing Architecture', function () {
        it('ensures test classes follow proper structure', function () {
            expect('Tests')
                ->toOnlyUse([
                    'PHPUnit\Framework\TestCase',
                    'Illuminate\Foundation\Testing\TestCase',
                    'Illuminate\Foundation\Testing\RefreshDatabase',
                    'Illuminate\Foundation\Testing\WithFaker',
                    'Tests\TestCase',
                    'App\Models',
                    'App\Enums',
                    'Laravel\Dusk\Browser',
                    'Carbon\Carbon',
                    'Illuminate\Support\Facades',
                ]);
        });

        it('ensures feature tests extend proper base', function () {
            expect('Tests\Feature')
                ->toExtend('Tests\TestCase');
        });

        it('ensures unit tests extend proper base', function () {
            expect('Tests\Unit')
                ->toExtend('Tests\TestCase');
        });
    });

    describe('Database Architecture', function () {
        it('ensures migrations follow proper structure', function () {
            expect('Database\Migrations')
                ->toOnlyUse([
                    'Illuminate\Database\Migrations\Migration',
                    'Illuminate\Database\Schema\Blueprint',
                    'Illuminate\Support\Facades\Schema',
                ]);
        });

        it('ensures factories follow proper structure', function () {
            expect('Database\Factories')
                ->toExtend('Illuminate\Database\Eloquent\Factories\Factory')
                ->toHaveMethod('definition');
        });

        it('ensures seeders follow proper structure', function () {
            expect('Database\Seeders')
                ->toExtend('Illuminate\Database\Seeder')
                ->toHaveMethod('run');
        });
    });

    describe('Security Architecture', function () {
        it('ensures no debug statements in production code', function () {
            expect('App')
                ->not()
                ->toUse([
                    'dd',
                    'dump',
                    'var_dump',
                    'print_r',
                ]);
        });

        it('ensures proper authorization is used', function () {
            expect('App\Http\Controllers')
                ->toOnlyUse([
                    'Illuminate\Foundation\Auth\Access\AuthorizesRequests',
                ])
                ->ignoring('App\Http\Controllers\Controller');
        });

        it('ensures models use proper mass assignment protection', function () {
            expect('App\Models')
                ->toHaveProperty('fillable')
                ->or()
                ->toHaveProperty('guarded');
        });
    });

    describe('Dependency Architecture', function () {
        it('ensures controllers do not depend on each other', function () {
            expect('App\Http\Controllers')
                ->not()
                ->toUse('App\Http\Controllers');
        });

        it('ensures models do not depend on controllers', function () {
            expect('App\Models')
                ->not()
                ->toUse('App\Http\Controllers');
        });

        it('ensures models do not depend on requests', function () {
            expect('App\Models')
                ->not()
                ->toUse('App\Http\Requests');
        });

        it('ensures requests do not depend on controllers', function () {
            expect('App\Http\Requests')
                ->not()
                ->toUse('App\Http\Controllers');
        });
    });

    describe('Code Quality Architecture', function () {
        it('ensures proper class naming conventions', function () {
            expect('App')
                ->toBeClasses()
                ->toUseStrictTypes();
        });

        it('ensures no global functions in app namespace', function () {
            expect('App')
                ->not()
                ->toHaveGlobalFunctions();
        });

        it('ensures proper interface implementations', function () {
            expect('App')
                ->interfaces()
                ->toOnlyBeUsedIn('App');
        });
    });
});