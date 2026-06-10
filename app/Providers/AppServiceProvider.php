<?php

namespace App\Providers;

use App\Contracts\AttendanceRepositoryInterface;
use App\Contracts\EmployeeRepositoryInterface;
use App\Contracts\PayrollRepositoryInterface;
use App\Repositories\EloquentAttendanceRepository;
use App\Repositories\EloquentEmployeeRepository;
use App\Repositories\EloquentPayrollRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, EloquentAttendanceRepository::class);
        $this->app->bind(PayrollRepositoryInterface::class, EloquentPayrollRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
