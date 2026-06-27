<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ChangeTemporaryPasswordController;
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
|
| Ruta inicial del sistema. Muestra la pantalla de bienvenida generada por
| Laravel Breeze/Inertia. No requiere autenticación.
|
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
| Cambio obligatorio de contraseña temporal
|--------------------------------------------------------------------------
|
| Estas rutas deben estar protegidas solo por autenticación.
| No deben usar el middleware "password.changed", porque justamente sirven
| para que el usuario cambie su contraseña temporal.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get(
        'cambiar-contrasena-temporal',
        [ChangeTemporaryPasswordController::class, 'edit']
    )->name('password.temporary.edit');

    Route::put(
        'cambiar-contrasena-temporal',
        [ChangeTemporaryPasswordController::class, 'update']
    )->name('password.temporary.update');
});

/*
|--------------------------------------------------------------------------
| Rutas principales protegidas
|--------------------------------------------------------------------------
|
| Estas rutas requieren:
| - Usuario autenticado.
| - Correo verificado.
| - Contraseña ya cambiada si el usuario fue creado con clave temporal.
|
| El middleware "password.changed" evita que un usuario con contraseña temporal
| pueda navegar por el sistema antes de actualizar su clave.
|
*/

Route::middleware(['auth', 'verified', 'password.changed'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | Pantalla principal del sistema después del inicio de sesión.
    |
    */

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Perfil de usuario
    |--------------------------------------------------------------------------
    |
    | Rutas propias de Breeze para editar datos del perfil autenticado.
    | Se mantienen dentro del sistema protegido.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Configuraciones Generales
    |--------------------------------------------------------------------------
    |
    | Catálogos reutilizables del sistema:
    | - Tipos de documento.
    | - Géneros.
    | - Estados civiles.
    | - Áreas.
    | - Cargos.
    | - Estados laborales.
    | - Sistemas pensionarios.
    | - Tipos de cuenta.
    |
    */

    Route::resource('catalogs', CatalogController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        'catalogs/{catalog}/toggle-status',
        [CatalogController::class, 'toggleStatus']
    )->name('catalogs.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Parámetros Laborales / Estructura Organizacional
    |--------------------------------------------------------------------------
    |
    | Vista agrupada para módulos pequeños de configuración empresarial.
    | Visualmente se administran juntos, pero cada módulo conserva su propio
    | controlador para mantener orden y escalabilidad.
    |
    */

    Route::get(
        'organizational-structure',
        [OrganizationalStructureController::class, 'index']
    )->name('organizational-structure.index');

    /*
    |--------------------------------------------------------------------------
    | Bancos
    |--------------------------------------------------------------------------
    |
    | Entidades financieras utilizadas para pagos y cuentas de trabajadores.
    |
    */

    Route::resource('banks', BankController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        'banks/{bank}/toggle-status',
        [BankController::class, 'toggleStatus']
    )->name('banks.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Turnos de trabajo
    |--------------------------------------------------------------------------
    |
    | Horarios laborales utilizados para asistencia, planillas y control interno.
    |
    */

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
    |
    | Gestión del personal de la empresa.
    | La URL se mantiene como "workers" para que sea más clara en la interfaz,
    | pero internamente usa EmployeeController.
    |
    */

    Route::resource('workers', EmployeeController::class)
        ->parameters(['workers' => 'worker'])
        ->except(['show', 'destroy']);

    Route::patch(
        'workers/{worker}/toggle-status',
        [EmployeeController::class, 'toggleStatus']
    )->name('workers.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Asistencia mensual
    |--------------------------------------------------------------------------
    |
    | Controla la cabecera mensual, el calendario diario y el cierre
    | de asistencia para luego generar planillas.
    |
    */
    Route::controller(AttendanceController::class)
        ->prefix('attendance')
        ->name('attendance.')
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::post('/', 'store')
                ->name('store');

            Route::get('{monthlyAttendance}/edit', 'edit')
                ->name('edit');

            Route::patch('{monthlyAttendance}/close', 'close')
                ->name('close');
        });

    Route::patch(
        'attendance-days/{attendanceDay}',
        [AttendanceController::class, 'updateDay']
    )->name('attendance.days.update');

    /*
    |--------------------------------------------------------------------------
    | Planillas
    |--------------------------------------------------------------------------
    |
    | Módulo pendiente de implementar con controlador propio.
    | Por ahora mantiene una vista base en Inertia.
    |
    */

    Route::get('/payrolls', function () {
        return Inertia::render('Payrolls/Index');
    })->name('payrolls.index');

    /*
    |--------------------------------------------------------------------------
    | Boletas de pago
    |--------------------------------------------------------------------------
    |
    | Módulo pendiente de implementar con controlador propio.
    | Por ahora mantiene una vista base en Inertia.
    |
    */

    Route::get('/payment-slips', function () {
        return Inertia::render('PaymentSlips/Index');
    })->name('payment-slips.index');

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    |
    | Módulo pendiente de implementar con controlador propio.
    | Por ahora mantiene una vista base en Inertia.
    |
    */

    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');

    /*
    |--------------------------------------------------------------------------
    | Seguridad - Usuarios
    |--------------------------------------------------------------------------
    |
    | Gestión de cuentas de acceso al sistema.
    | Aquí también se asignan roles a cada usuario.
    |
    */

    Route::resource('users', UserController::class)
        ->except(['show', 'destroy']);

    Route::patch(
        'users/{user}/toggle-status',
        [UserController::class, 'toggleStatus']
    )->name('users.toggle-status');

    Route::patch(
        'users/{user}/reset-password',
        [UserController::class, 'resetPassword']
    )->name('users.reset-password');

    /*
    |--------------------------------------------------------------------------
    | Seguridad - Roles y Permisos
    |--------------------------------------------------------------------------
    |
    | Gestión de perfiles de acceso.
    | Los permisos se crean desde el seeder, y desde este CRUD se asignan a roles.
    |
    */

    Route::resource('roles', RoleController::class)
        ->except(['show', 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
|
| Rutas generadas por Laravel Breeze:
| - Login.
| - Logout.
| - Recuperación de contraseña.
| - Confirmación de contraseña.
| - Verificación de correo.
|
*/

require __DIR__ . '/auth.php';
