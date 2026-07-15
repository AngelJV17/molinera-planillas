<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Eye, FileDown, FileSpreadsheet, ReceiptText, SearchCheck } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import ListSummary from '@/Components/Common/ListSummary.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';
import { formatPeriod, periodForRequest } from '@/Utils/dates';

const props = defineProps({
    slips: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search ?? '');
const period = ref(formatPeriod(props.filters.period ?? ''));
const perPage = ref(props.filters.per_page ?? 10);
let filterTimeout = null;

const columns = [
    { key: 'worker', label: 'Trabajador' },
    { key: 'period', label: 'Periodo' },
    { key: 'income', label: 'Ingresos' },
    { key: 'discount', label: 'Descuentos' },
    { key: 'net', label: 'Neto' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const totalNet = computed(() => {
    return props.slips.data.reduce((sum, slip) => sum + Number(slip.net_pay ?? 0), 0);
});

const applyFilters = () => {
    router.get(
        route('payment-slips.index'),
        {
            search: search.value || undefined,
            period: periodForRequest(period.value) || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, period, perPage], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => applyFilters(), 350);
});

const money = (amount) => `S/ ${Number(amount ?? 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})}`;

const statusClass = (statusCode) => {
    const classes = {
        APPROVED: 'bg-primary/15 text-primary',
        PAID: 'bg-emerald-100 text-emerald-800',
    };

    return classes[statusCode] ?? 'bg-slate-100 text-slate-700';
};
</script>

<template>
    <Head title="Boletas" />

    <AuthenticatedLayout title="Boletas">
        <section class="space-y-6">
            <PageHeader
                title="Boletas de pago"
                description="Consulta e imprime las boletas emitidas desde planillas aprobadas o pagadas."
            >
                <template #icon>
                    <ReceiptText class="h-7 w-7" />
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar trabajador, codigo o DNI..." />

                    <SearchInput v-model="period" placeholder="Periodo MM-YYYY..." />

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary
                title="Boletas disponibles"
                description="Solo se muestran boletas de planillas aprobadas o pagadas."
                label="Total registros"
                :total="slips.total ?? slips.data.length"
                :icon="SearchCheck"
            >
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                        Neto filtrado: {{ money(totalNet) }}
                    </span>
                </div>
            </ListSummary>

            <DataTable :columns="columns">
                <tr v-for="slip in slips.data" :key="slip.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell
                            :icon="ReceiptText"
                            :title="slip.employee_name"
                            :subtitle="`${slip.employee_code} - DNI ${slip.document_number}`"
                            :meta="slip.pension_system_name"
                        />
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        <p class="font-bold text-gray-800">{{ slip.period }}</p>
                        <p class="text-xs text-gray-500">{{ slip.payroll_code }}</p>
                    </td>
                    <td class="px-6 py-4 font-bold text-gray-700">{{ money(slip.total_income) }}</td>
                    <td class="px-6 py-4 font-bold text-danger">{{ money(slip.total_discount) }}</td>
                    <td class="px-6 py-4 font-black text-primary-dark">{{ money(slip.net_pay) }}</td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-black" :class="statusClass(slip.status?.code)">
                            {{ slip.status?.name ?? 'Sin estado' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <TableActions>
                            <a
                                :href="route('payment-slips.print', slip.id)"
                                title="Ver boleta imprimible"
                                aria-label="Ver boleta imprimible"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                                target="_blank"
                                rel="noopener"
                            >
                                <Eye class="h-4 w-4" />
                            </a>
                            <a
                                :href="route('payment-slips.excel', slip.id)"
                                title="Descargar Excel"
                                aria-label="Descargar Excel"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                            >
                                <FileSpreadsheet class="h-4 w-4" />
                            </a>
                            <a
                                :href="route('payment-slips.pdf', slip.id)"
                                title="Descargar PDF"
                                aria-label="Descargar PDF"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                            >
                                <FileDown class="h-4 w-4" />
                            </a>
                        </TableActions>
                    </td>
                </tr>

                <template v-if="slips.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState
                            title="No hay boletas disponibles"
                            description="Aprueba o paga una planilla para habilitar las boletas de sus trabajadores."
                        />
                    </td>
                </template>
            </DataTable>

            <div v-if="slips.links?.length > 3" class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="slips.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
