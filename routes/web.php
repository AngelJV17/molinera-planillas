<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceExchangeController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ChangeTemporaryPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OrganizationalStructureController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollParameterController;
use App\Http\Controllers\PaymentSlipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
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
})->name('welcome');

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

    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

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
        ->except(['show', 'destroy'])
        ->middlewareFor(['index'], 'permission:catalogs.view')
        ->middlewareFor(['create', 'store'], 'permission:catalogs.create')
        ->middlewareFor(['edit', 'update'], 'permission:catalogs.edit');

    Route::patch(
        'catalogs/{catalog}/toggle-status',
        [CatalogController::class, 'toggleStatus']
    )->middleware('permission:catalogs.toggle-status')->name('catalogs.toggle-status');

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
    )->middleware('permission:banks.view|work-shifts.view')->name('organizational-structure.index');

    /*
    |--------------------------------------------------------------------------
    | Bancos
    |--------------------------------------------------------------------------
    |
    | Entidades financieras utilizadas para pagos y cuentas de trabajadores.
    |
    */

    Route::resource('banks', BankController::class)
        ->except(['show', 'destroy'])
        ->middlewareFor(['index'], 'permission:banks.view')
        ->middlewareFor(['create', 'store'], 'permission:banks.create')
        ->middlewareFor(['edit', 'update'], 'permission:banks.edit');

    Route::patch(
        'banks/{bank}/toggle-status',
        [BankController::class, 'toggleStatus']
    )->middleware('permission:banks.toggle-status')->name('banks.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Turnos de trabajo
    |--------------------------------------------------------------------------
    |
    | Horarios laborales utilizados para asistencia, planillas y control interno.
    |
    */

    Route::resource('work-shifts', WorkShiftController::class)
        ->except(['show', 'destroy'])
        ->middlewareFor(['index'], 'permission:work-shifts.view')
        ->middlewareFor(['create', 'store'], 'permission:work-shifts.create')
        ->middlewareFor(['edit', 'update'], 'permission:work-shifts.edit');

    Route::patch(
        'work-shifts/{work_shift}/toggle-status',
        [WorkShiftController::class, 'toggleStatus']
    )->middleware('permission:work-shifts.toggle-status')->name('work-shifts.toggle-status');

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
        ->except(['show', 'destroy'])
        ->middlewareFor(['index'], 'permission:workers.view')
        ->middlewareFor(['create', 'store'], 'permission:workers.create')
        ->middlewareFor(['edit', 'update'], 'permission:workers.edit');

    Route::patch(
        'workers/{worker}/toggle-status',
        [EmployeeController::class, 'toggleStatus']
    )->middleware('permission:workers.toggle-status')->name('workers.toggle-status');

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
                ->middleware('permission:attendance.view')
                ->name('index');

            Route::post('/', 'store')
                ->middleware('permission:attendance.create')
                ->name('store');

            Route::get('{monthlyAttendance}/edit', 'edit')
                ->middleware('permission:attendance.edit')
                ->name('edit');

            Route::patch('{monthlyAttendance}/close', 'close')
                ->middleware('permission:attendance.close')
                ->name('close');

            Route::patch('{monthlyAttendance}/reopen', 'reopen')
                ->middleware('permission:attendance.reopen')
                ->name('reopen');

            Route::patch('{monthlyAttendance}/days/bulk', 'bulkUpdateDays')
                ->middleware('permission:attendance.edit')
                ->name('days.bulk-update');
        });

    Route::patch(
        'attendance-days/{attendanceDay}',
        [AttendanceController::class, 'updateDay']
    )->middleware('permission:attendance.edit')->name('attendance.days.update');

    Route::get('attendance-exchanges', [AttendanceExchangeController::class, 'index'])
        ->middleware('permission:attendance-exchanges.view')
        ->name('attendance-exchanges.index');

    /*
    |--------------------------------------------------------------------------
    | Planillas
    |--------------------------------------------------------------------------
    |
    | Módulo pendiente de implementar con controlador propio.
    | Por ahora mantiene una vista base en Inertia.
    |
    */

    Route::controller(PayrollController::class)
        ->prefix('payrolls')
        ->name('payrolls.')
        ->group(function () {
            Route::get('/', 'index')->middleware('permission:payrolls.view')->name('index');
            Route::post('/', 'store')->middleware('permission:payrolls.create')->name('store');
            Route::patch('{payroll}/approve', 'approve')->middleware('permission:payrolls.approve')->name('approve');
            Route::patch('{payroll}/observe', 'observe')->middleware('permission:payrolls.observe')->name('observe');
            Route::patch('{payroll}/reject', 'reject')->middleware('permission:payrolls.reject')->name('reject');
            Route::patch('{payroll}/recalculate', 'recalculate')->middleware('permission:payrolls.recalculate')->name('recalculate');
            Route::patch('{payroll}/pay', 'pay')->middleware('permission:payrolls.pay')->name('pay');
        });

    Route::resource('payroll-parameters', PayrollParameterController::class)
        ->parameters(['payroll-parameters' => 'payroll_parameter'])
        ->except(['show', 'destroy'])
        ->middlewareFor(['index'], 'permission:payroll-parameters.view')
        ->middlewareFor(['create', 'store'], 'permission:payroll-parameters.create')
        ->middlewareFor(['edit', 'update'], 'permission:payroll-parameters.edit');

    Route::patch(
        'payroll-parameters/{payroll_parameter}/toggle-status',
        [PayrollParameterController::class, 'toggleStatus']
    )->middleware('permission:payroll-parameters.toggle-status')->name('payroll-parameters.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Boletas de pago
    |--------------------------------------------------------------------------
    |
    | Módulo pendiente de implementar con controlador propio.
    | Por ahora mantiene una vista base en Inertia.
    |
    */

    Route::controller(PaymentSlipController::class)
        ->prefix('payment-slips')
        ->name('payment-slips.')
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:payment-slips.view')
                ->name('index');

            Route::get('{paymentSlip}/print', 'print')
                ->middleware('permission:payment-slips.download')
                ->name('print');

            Route::get('{paymentSlip}/pdf', 'pdf')
                ->middleware('permission:payment-slips.download')
                ->name('pdf');

            Route::get('{paymentSlip}/excel', 'excel')
                ->middleware('permission:payment-slips.download')
                ->name('excel');
        });

    /*
    |--------------------------------------------------------------------------
    | Reportes
    |--------------------------------------------------------------------------
    |
    | Módulo pendiente de implementar con controlador propio.
    | Por ahora mantiene una vista base en Inertia.
    |
    */

    Route::controller(ReportController::class)
        ->prefix('reports')
        ->name('reports.')
        ->group(function () {
            Route::get('/', 'index')
                ->middleware('permission:reports.view')
                ->name('index');

            Route::get('export', 'export')
                ->middleware('permission:reports.export')
                ->name('export');
        });

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
        ->except(['show', 'destroy'])
        ->middlewareFor(['index'], 'permission:users.view')
        ->middlewareFor(['create', 'store'], 'permission:users.create')
        ->middlewareFor(['edit', 'update'], 'permission:users.edit');

    Route::patch(
        'users/{user}/toggle-status',
        [UserController::class, 'toggleStatus']
    )->middleware('permission:users.toggle-status')->name('users.toggle-status');

    Route::patch(
        'users/{user}/reset-password',
        [UserController::class, 'resetPassword']
    )->middleware('permission:users.edit')->name('users.reset-password');

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
        ->except(['show', 'destroy'])
        ->middlewareFor(['index'], 'permission:roles.view')
        ->middlewareFor(['create', 'store'], 'permission:roles.create')
        ->middlewareFor(['edit', 'update'], 'permission:roles.edit|roles.assign-permissions');
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
