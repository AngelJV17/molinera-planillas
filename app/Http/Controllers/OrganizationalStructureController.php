<?php
namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\WorkShift;
use Inertia\Response;
use Inertia\Inertia;

class OrganizationalStructureController extends Controller
{
    /**
     * =========================================================================
     * ESTRUCTURA ORGANIZACIONAL
     * =========================================================================
     *
     * Centraliza la administración de:
     * - Bancos
     * - Turnos
     * - Áreas (próximamente)
     * - Cargos (próximamente)
     *
     * Esta vista agrupa catálogos organizacionales de bajo volumen
     * para simplificar la navegación del usuario.
     *
     * =========================================================================
     */
    public function index(): Response
    {
        return Inertia::render(
            'OrganizationalStructure/Index',
            [
                /*
                |--------------------------------------------------------------------------
                | Bancos
                |--------------------------------------------------------------------------
                */
                'banks'      => Bank::query()
                    ->orderBy('name')
                    ->get(),

                /*
                |--------------------------------------------------------------------------
                | Turnos
                |--------------------------------------------------------------------------
                */
                'workShifts' => WorkShift::query()
                    ->orderBy('name')
                    ->get(),

                /*
                |--------------------------------------------------------------------------
                | Próximos módulos
                |--------------------------------------------------------------------------
                */
                'areas'      => [],

                'positions'  => [],
            ]
        );
    }
}
