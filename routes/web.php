<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OrganizationalStructureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkShiftController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Página pública
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Configuraciones Generales
    |--------------------------------------------------------------------------
    */

    Route::resource('catalogs', CatalogController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        'catalogs/{catalog}/toggle-status',
        [CatalogController::class, 'toggleStatus']
    )->name('catalogs.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Estructura Organizacional
    |--------------------------------------------------------------------------
    |
    | Vista agrupada para módulos pequeños de configuración empresarial.
    |
    | Aquí se mostrarán visualmente:
    | - Bancos
    | - Turnos
    | - Áreas      próximamente
    | - Cargos     próximamente
    |
    */

    Route::get(
        'organizational-structure',
        [OrganizationalStructureController::class, 'index']
    )->name('organizational-structure.index');

    /*
    |--------------------------------------------------------------------------
    | CRUD internos de Estructura Organizacional
    |--------------------------------------------------------------------------
    |
    | Aunque visualmente Bancos y Turnos se agrupen en una sola vista,
    | sus controladores y rutas se mantienen separados para conservar
    | orden, escalabilidad y buenas prácticas.
    |
    | Se mantienen los index para no romper rutas existentes como:
    | - banks.index
    | - work-shifts.index
    |
    */

    Route::resource('banks', BankController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        'banks/{bank}/toggle-status',
        [BankController::class, 'toggleStatus']
    )->name('banks.toggle-status');

    Route::resource('work-shifts', WorkShiftController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        'work-shifts/{work_shift}/toggle-status',
        [WorkShiftController::class, 'toggleStatus']
    )->name('work-shifts.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Recursos Humanos
    |--------------------------------------------------------------------------
    */

    Route::resource('workers', EmployeeController::class)
        ->parameters(['workers' => 'worker'])
        ->except(['show', 'destroy']);

    Route::patch(
        'workers/{worker}/toggle-status',
        [EmployeeController::class, 'toggleStatus']
    )->name('workers.toggle-status');

    Route::get('/attendance', function () {
        return Inertia::render('Attendance/Index');
    })->name('attendance.index');

    /*
    |--------------------------------------------------------------------------
    | Planillas
    |--------------------------------------------------------------------------
    */

    Route::get('/payrolls', function () {
        return Inertia::render('Payrolls/Index');
    })->name('payrolls.index');

    Route::get('/payment-slips', function () {
        return Inertia::render('PaymentSlips/Index');
    })->name('payment-slips.index');

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    */

    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');

    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    Route::get('users', [UserController::class, 'index'])
        ->name('users.index');

    Route::resource('users', UserController::class)
        ->except(['show', 'destroy']);

    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');

    Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.reset-password');

});

/*
|--------------------------------------------------------------------------
| Perfil de usuario
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');

    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');

    Route::get('roles', [RoleController::class, 'index'])
        ->name('roles.index');

    Route::resource('roles', RoleController::class)
        ->except(['show', 'destroy']);

});

require __DIR__ . '/auth.php';
