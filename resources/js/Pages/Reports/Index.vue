<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import { BarChart3, Download, FileText, ShieldCheck } from 'lucide-vue-next';

const reportFilters = ref({
    periodo: 'Junio 2026',
    tipo: 'Planilla mensual',
});

const reports = [
    { id: 1, nombre: 'Resumen de remuneraciones', periodo: 'Junio 2026', estado: 'Aprobado' },
    { id: 2, nombre: 'Horas extras y canjeables', periodo: 'Junio 2026', estado: 'Pendiente' },
    { id: 3, nombre: 'Faltas y tardanzas', periodo: 'Junio 2026', estado: 'Observado' },
];

const columns = [
    { key: 'nombre', label: 'Reporte' },
    { key: 'periodo', label: 'Periodo' },
    { key: 'estado', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const statusClass = (status) => ({
    Aprobado: 'bg-green-100 text-green-700',
    Pendiente: 'bg-secondary/15 text-secondary',
    Observado: 'bg-danger/15 text-danger',
}[status]);
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout title="Reportes">
        <section class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="rounded-3xl border border-primary/20 bg-white p-6 shadow-lg">
                    <div class="flex gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                            <BarChart3 class="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-gray-900">Reportes administrativos</h1>
                            <p class="mt-1 text-sm text-gray-600">
                                Consolida informacion de planillas, asistencia y boletas para revision contable.
                            </p>
                        </div>
                    </div>
                </div>

                <button type="button"
                    class="flex min-h-32 flex-col items-start justify-center rounded-3xl bg-primary p-6 text-left text-white shadow-lg transition hover:bg-primary-dark">
                    <ShieldCheck class="h-8 w-8" />
                    <span class="mt-4 text-lg font-black">Exportar Estructura PLAME (.txt)</span>
                    <span class="mt-1 text-sm text-white/80">Simulacion lista para el Contador.</span>
                </button>
            </div>

            <form class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-md md:grid-cols-2">
                <label class="text-sm font-semibold text-gray-700">
                    Periodo
                    <input v-model="reportFilters.periodo" type="text"
                        class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                </label>
                <label class="text-sm font-semibold text-gray-700">
                    Tipo de reporte
                    <select v-model="reportFilters.tipo"
                        class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary">
                        <option>Planilla mensual</option>
                        <option>Asistencia mensual</option>
                        <option>Boletas emitidas</option>
                        <option>Estructura PLAME</option>
                    </select>
                </label>
            </form>

            <DataTable :columns="columns">
                <tr v-for="report in reports" :key="report.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <FileText class="h-5 w-5" />
                            </div>
                            <span class="font-bold text-gray-800">{{ report.nombre }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ report.periodo }}</td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-bold" :class="statusClass(report.estado)">
                            {{ report.estado }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-end">
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-primary/10 hover:text-primary">
                                <Download class="h-4 w-4" />
                                Descargar
                            </button>
                        </div>
                    </td>
                </tr>
            </DataTable>
        </section>
    </AuthenticatedLayout>
</template>
