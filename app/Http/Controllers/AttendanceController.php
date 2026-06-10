<?php

namespace App\Http\Controllers;

use App\DTOs\AttendanceImportDTO;
use App\Http\Requests\StoreAttendanceRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendances,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Attendance/Index');
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $this->attendances->saveDailyDetail(AttendanceImportDTO::fromArray($request->validated()));

        return back()->with('success', 'Asistencia registrada correctamente.');
    }
}
