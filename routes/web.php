<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('catalogs', CatalogController::class)
        ->except(['show', 'destroy']);

    Route::patch('catalogs/{catalog}/toggle-status', [CatalogController::class, 'toggleStatus'])
        ->name('catalogs.toggle-status');
    // Rutas para las páginas de trabajadores, asistencia, planillas, recibos de pago, reportes y usuarios
    Route::resource('workers', EmployeeController::class)
        ->only(['index', 'store', 'update'])
        ->parameters(['workers' => 'employee']);

    Route::resource('attendance', AttendanceController::class)
        ->only(['index', 'store']);

    Route::resource('payrolls', PayrollController::class)
        ->only(['index', 'store']);

    Route::patch('payrolls/{payroll}/approve', [PayrollController::class, 'approve'])
        ->name('payrolls.approve');

    Route::patch('payrolls/{payroll}/reject', [PayrollController::class, 'reject'])
        ->name('payrolls.reject');

    Route::get('/payment-slips', function () {
        return Inertia::render('PaymentSlips/Index');
    })->name('payment-slips.index');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/payrolls/{payroll}/plame', [ReportController::class, 'exportPlame'])
        ->name('reports.plame');

    Route::get('/users', function () {
        return Inertia::render('Users/Index');
    })->name('users.index');    
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
