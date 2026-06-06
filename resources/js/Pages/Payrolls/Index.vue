<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import { Calculator, FileSpreadsheet, Play, Save } from 'lucide-vue-next';

const periodForm = reactive({
    periodo: 'Junio 2026',
    fechaPago: '2026-06-30',
    observacion: 'Planilla mensual de trabajadores activos.',
});

const payrollRows = ref([
    { id: 1, trabajador: 'Juan Perez Huaman', sueldo: 1450, familiar: 113, extras: 85, descuentos: 180, neto: 1468 },
    { id: 2, trabajador: 'Maria Quispe Rojas', sueldo: 1800, familiar: 0, extras: 0, descuentos: 235, neto: 1565 },
    { id: 3, trabajador: 'Carlos Mendoza Salas', sueldo: 2450, familiar: 113, extras: 160, descuentos: 330, neto: 2393 },
]);

const columns = [
    { key: 'trabajador', label: 'Trabajador' },
    { key: 'sueldo', label: 'Sueldo' },
    { key: 'familiar', label: 'Asig. familiar' },
    { key: 'extras', label: 'Horas extras' },
    { key: 'descuentos', label: 'Descuentos' },
    { key: 'neto', label: 'Neto' },
];

const money = (amount) => `S/ ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
const totalNeto = () => payrollRows.value.reduce((sum, row) => sum + row.neto, 0);
</script>

<template>
    <Head title="Planillas" />

    <AuthenticatedLayout title="Planillas">
        <section class="space-y-6">
            <div class="overflow-hidden rounded-3xl border border-primary/20 bg-white shadow-lg">
                <div class="h-1.5 bg-primary"></div>
                <div class="grid gap-6 p-6 lg:grid-cols-[1fr_320px]">
                    <div class="flex gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                            <FileSpreadsheet class="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-gray-900">Generacion de planillas</h1>
                            <p class="mt-1 max-w-3xl text-sm text-gray-600">
                                Simula el calculo mensual con remuneraciones, asignacion familiar, horas extras y descuentos.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-primary/10 p-4">
                        <p class="text-sm font-bold text-primary">Total neto estimado</p>
                        <p class="mt-2 text-3xl font-black text-primary-dark">{{ money(totalNeto()) }}</p>
                    </div>
                </div>
            </div>

            <form class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-md md:grid-cols-3">
                <label class="text-sm font-semibold text-gray-700">
                    Periodo
                    <input v-model="periodForm.periodo" type="text"
                        class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    Fecha de pago
                    <input v-model="periodForm.fechaPago" type="date"
                        class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    Observacion
                    <input v-model="periodForm.observacion" type="text"
                        class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                </label>
            </form>

            <DataTable :columns="columns">
                <tr v-for="row in payrollRows" :key="row.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4 font-bold text-gray-800">{{ row.trabajador }}</td>
                    <td class="px-6 py-4">{{ money(row.sueldo) }}</td>
                    <td class="px-6 py-4">{{ money(row.familiar) }}</td>
                    <td class="px-6 py-4">{{ money(row.extras) }}</td>
                    <td class="px-6 py-4 text-danger">{{ money(row.descuentos) }}</td>
                    <td class="px-6 py-4 font-black text-primary-dark">{{ money(row.neto) }}</td>
                </tr>
            </DataTable>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary px-5 py-3 text-sm font-bold text-primary hover:bg-primary/10">
                    <Calculator class="h-4 w-4" />
                    Recalcular
                </button>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white hover:bg-primary-dark">
                    <Play class="h-4 w-4" />
                    Generar planilla
                </button>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white hover:brightness-95">
                    <Save class="h-4 w-4" />
                    Guardar borrador
                </button>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
