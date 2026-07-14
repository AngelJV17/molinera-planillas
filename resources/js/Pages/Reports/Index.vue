<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { BarChart3, FileDown, FileSpreadsheet, FileText } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import TableActions from '@/Components/Table/TableActions.vue';

const props = defineProps({
    reports: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const period = ref(props.filters.period ?? '');
let filterTimeout = null;

const columns = [
    { key: 'report', label: 'Reporte' },
    { key: 'records', label: 'Registros' },
    { key: 'format', label: 'Formato' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

watch(period, () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route('reports.index'),
            { period: period.value || undefined },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 350);
});

const exportUrl = (type, format) => route('reports.export', {
    type,
    format,
    period: period.value || undefined,
});
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout title="Reportes">
        <section class="space-y-6">
            <PageHeader
                title="Reportes"
                description="Exporta informacion operativa de planillas, asistencias y boletas."
            >
                <template #icon>
                    <BarChart3 class="h-7 w-7" />
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="period" placeholder="Periodo YYYY-MM..." />
                </template>
            </FilterCard>

            <DataTable :columns="columns">
                <tr v-for="report in reports" :key="report.type" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <FileText class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-black text-gray-900">{{ report.name }}</p>
                                <p class="text-xs text-gray-500">{{ report.description }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-black text-gray-800">
                        {{ report.records }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">
                            XLSX / PDF
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <TableActions>
                            <a
                                :href="exportUrl(report.type, 'xlsx')"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                            >
                                <FileSpreadsheet class="h-4 w-4" />
                                Excel
                            </a>
                            <a
                                :href="exportUrl(report.type, 'pdf')"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                            >
                                <FileDown class="h-4 w-4" />
                                PDF
                            </a>
                        </TableActions>
                    </td>
                </tr>

                <template v-if="reports.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState
                            title="No hay reportes disponibles"
                            description="Genera asistencia o planillas para habilitar reportes del periodo."
                        />
                    </td>
                </template>
            </DataTable>
        </section>
    </AuthenticatedLayout>
</template>
