<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { Calculator, FileSpreadsheet, Play, Save } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import DataTable from '@/Components/Table/DataTable.vue';

const periodForm = reactive({
    periodo: 'Junio 2026',
    fechaPago: '2026-06-30',
    observacion: 'Planilla mensual de trabajadores activos.',
});

const payrollRows = ref([
    { id: 1, trabajador: 'Juan Pérez Huamán', sueldo: 1450, familiar: 113, extras: 85, descuentos: 180, neto: 1468 },
    { id: 2, trabajador: 'María Quispe Rojas', sueldo: 1800, familiar: 0, extras: 0, descuentos: 235, neto: 1565 },
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
            <PageHeader
                title="Generación de planillas"
                description="Simula el cálculo mensual con remuneraciones, asignación familiar, horas extras y descuentos."
            >
                <template #icon>
                    <FileSpreadsheet class="h-7 w-7" />
                </template>

                <template #stats>
                    <div class="inline-flex rounded-2xl bg-primary/10 px-4 py-3">
                        <div>
                            <p class="text-sm font-bold text-primary">Total neto estimado</p>
                            <p class="mt-1 text-2xl font-black text-primary-dark">{{ money(totalNeto()) }}</p>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <SectionCard title="Periodo de planilla" description="Define el periodo, fecha de pago y observación del cálculo.">
                <form class="grid gap-4 md:grid-cols-3">
                    <label class="text-sm font-semibold text-gray-700">
                        Periodo
                        <input v-model="periodForm.periodo" type="text" class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Fecha de pago
                        <input v-model="periodForm.fechaPago" type="date" class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Observación
                        <input v-model="periodForm.observacion" type="text" class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    </label>
                </form>
            </SectionCard>

            <section class="space-y-4">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Detalle de trabajadores</h2>
                    <p class="text-sm text-gray-500">Resumen de ingresos, descuentos y neto estimado.</p>
                </div>

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
            </section>

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
