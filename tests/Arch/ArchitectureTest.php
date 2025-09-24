<?php

use App\Models\User;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

describe('Application Architecture', function (): void {

    describe('Models Architecture', function (): void {
        it('ensures models are properly structured', function (): void {
            expect('App\Models')
                ->toOnlyUse([
                    Model::class,
                    HasFactory::class,
                    SoftDeletes::class,
                    'Illuminate\Database\Eloquent\Relations',
                    \Illuminate\Database\Eloquent\Casts\Attribute::class,
                    Builder::class,
                    Collection::class,
                    \Illuminate\Support\Carbon::class,
                    Carbon::class,
                    'App\Enums',
                    LogsActivity::class,
                    LogOptions::class,
                ]);
        });

        it('ensures models extend Eloquent Model', function (): void {
            expect('App\Models')
                ->toExtend(Model::class);
        });

        it('ensures models use HasFactory trait', function (): void {
            expect('App\Models')
                ->toUse(HasFactory::class);
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
                    Request::class,
                    Response::class,
                    RedirectResponse::class,
                    JsonResponse::class,
                    Controller::class,
                    AuthorizesRequests::class,
                    ValidatesRequests::class,
                    View::class,
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
                    Request::class,
                    RedirectResponse::class,
                    View::class,
                    Registered::class,
                    Auth::class,
                    Hash::class,
                    'Illuminate\Validation\Rules',
                    \App\Http\Controllers\Controller::class,
                    User::class,
                    'App\Http\Requests\Auth',
                    'App\Providers\RouteServiceProvider',
                ]);
        });
    });

    describe('Requests Architecture', function (): void {
        it('ensures requests have proper structure', function (): void {
            expect('App\Http\Requests')
                ->toExtend(FormRequest::class)
                ->toHaveMethod('rules')
                ->toHaveMethod('authorize');
        });

        it('ensures request classes follow naming conventions', function (): void {
            expect('App\Http\Requests')
                ->toOnlyUse([
                    FormRequest::class,
                    Rule::class,
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
                    Request::class,
                    Response::class,
                    \Symfony\Component\HttpFoundation\Response::class,
                    'Closure',
                    Authenticate::class,
                    VerifyCsrfToken::class,
                    TrustProxies::class,
                    SubstituteBindings::class,
                    ShareErrorsFromSession::class,
                    'App\Models',
                ]);
        });
    });

    describe('Observers Architecture', function (): void {
        it('ensures observers follow proper structure', function (): void {
            expect('App\Observers')
                ->toOnlyUse([
                    'App\Models',
                    'App\Notifications',
                    Log::class,
                    Activity::class,
                ]);
        });
    });

    describe('Providers Architecture', function (): void {
        it('ensures providers extend ServiceProvider', function (): void {
            expect('App\Providers')
                ->toExtend(ServiceProvider::class)
                ->toHaveMethod('register')
                ->toHaveMethod('boot');
        });

        it('ensures providers follow Laravel conventions', function (): void {
            expect('App\Providers')
                ->toOnlyUse([
                    ServiceProvider::class,
                    Gate::class,
                    AuthServiceProvider::class,
                    Limit::class,
                    Request::class,
                    RateLimiter::class,
                    Route::class,
                    RouteServiceProvider::class,
                    ResetPassword::class,
                    'App\Models',
                    'App\Observers',
                    LanguageSwitch::class,
                ]);
        });
    });

    describe('Filament Resources Architecture', function (): void {
        it('ensures Filament resources follow proper structure', function (): void {
            expect('App\Filament\Resources')
                ->toOnlyUse([
                    'Filament\Forms',
                    'Filament\Resources',
                    'Filament\Tables',
                    'Filament\Actions',
                    'Filament\Infolists',
                    Builder::class,
                    SoftDeletingScope::class,
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
                    TestCase::class,
                    RefreshDatabase::class,
                    WithFaker::class,
                    \Tests\TestCase::class,
                    'App\Models',
                    'App\Enums',
                    'Laravel\Dusk\Browser',
                    Carbon::class,
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
                    Migration::class,
                    Blueprint::class,
                    Schema::class,
                ]);
        });

        it('ensures factories follow proper structure', function (): void {
            expect('Database\Factories')
                ->toExtend(Factory::class)
                ->toHaveMethod('definition');
        });

        it('ensures seeders follow proper structure', function (): void {
            expect('Database\Seeders')
                ->toExtend(Seeder::class)
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
                    AuthorizesRequests::class,
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
                ->ignoring('App\Enums')
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
