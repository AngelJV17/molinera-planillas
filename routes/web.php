<?php

use App\Http\Controllers\BankController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProfileController;
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

Route::resource(
    'banks',
    BankController::class
);

Route::patch(
    'banks/{bank}/toggle-status',
    [BankController::class, 'toggleStatus']
)->name('banks.toggle-status');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('catalogs', CatalogController::class)
        ->except(['show', 'destroy']);

    Route::patch('catalogs/{catalog}/toggle-status', [CatalogController::class, 'toggleStatus'])
        ->name('catalogs.toggle-status');
    // Rutas para las páginas de trabajadores, asistencia, planillas, recibos de pago, reportes y usuarios
    Route::get('/workers', function () {
        return Inertia::render('Workers/Index');
    })->name('workers.index');

    Route::get('/attendance', function () {
        return Inertia::render('Attendance/Index');
    })->name('attendance.index');

    Route::get('/payrolls', function () {
        return Inertia::render('Payrolls/Index');
    })->name('payrolls.index');

    Route::get('/payment-slips', function () {
        return Inertia::render('PaymentSlips/Index');
    })->name('payment-slips.index');

    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');

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
