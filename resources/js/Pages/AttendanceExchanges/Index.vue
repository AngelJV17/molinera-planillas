<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { CalendarCheck2, Database } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import ListSummary from '@/Components/Common/ListSummary.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';

const props = defineProps({
    exchanges: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statuses: {
        type: Array,
        default: () => [],
    },
});

const search = ref(props.filters.search ?? '');
const statusId = ref(props.filters.status_id ?? '');
const period = ref(props.filters.period ?? '');
const perPage = ref(props.filters.per_page ?? 10);
let filterTimeout = null;

const columns = [
    { key: 'worker', label: 'Trabajador' },
    { key: 'dates', label: 'Fechas' },
    { key: 'status', label: 'Estado' },
    { key: 'registered', label: 'Registro' },
    { key: 'observation', label: 'Observacion' },
];

const appliedCount = computed(() => {
    return props.exchanges.data.filter((exchange) => exchange.status?.code === 'APPLIED').length;
});

const applyFilters = () => {
    router.get(
        route('attendance-exchanges.index'),
        {
            search: search.value || undefined,
            status_id: statusId.value || undefined,
            period: period.value || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, statusId, period, perPage], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => applyFilters(), 350);
});

const statusClass = (statusCode) => {
    const classes = {
        APPLIED: 'bg-emerald-100 text-emerald-800',
        PENDING: 'bg-amber-100 text-amber-800',
        CANCELLED: 'bg-danger/15 text-danger',
    };

    return classes[statusCode] ?? 'bg-slate-100 text-slate-700';
};
</script>

<template>
    <Head title="Canjes" />

    <AuthenticatedLayout title="Canjes">
        <section class="space-y-6">
            <PageHeader
                title="Canjes de asistencia"
                description="Consulta la trazabilidad de faltas compensadas con dias trabajados."
            >
                <template #icon>
                    <CalendarCheck2 class="h-7 w-7" />
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar trabajador, codigo o DNI..." />

                    <SearchInput v-model="period" placeholder="Periodo falta YYYY-MM..." />

                    <select
                        v-model="statusId"
                        class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary"
                    >
                        <option value="">Todos los estados</option>
                        <option v-for="status in statuses" :key="status.id" :value="status.id">
                            {{ status.name }}
                        </option>
                    </select>

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary
                title="Canjes registrados"
                description="La falta y el dia trabajado quedan vinculados para sustentar el descuento o la compensacion."
                label="Total registros"
                :total="exchanges.total ?? exchanges.data.length"
                :icon="Database"
            >
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                        Aplicados visibles: {{ appliedCount }}
                    </span>
                </div>
            </ListSummary>

            <DataTable :columns="columns">
                <tr v-for="exchange in exchanges.data" :key="exchange.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell
                            :icon="CalendarCheck2"
                            :title="exchange.employee?.name ?? 'Trabajador no disponible'"
                            :subtitle="`${exchange.employee?.code ?? 'Sin codigo'} - DNI ${exchange.employee?.document_number ?? '-'}`"
                            :meta="`Canje #${exchange.id}`"
                        />
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        <p><strong>Falta:</strong> {{ exchange.absence_date }}</p>
                        <p><strong>Compensa:</strong> {{ exchange.exchange_date }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-black" :class="statusClass(exchange.status?.code)">
                            {{ exchange.status?.name ?? 'Sin estado' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ exchange.registered_by ?? 'Sistema' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ exchange.observation ?? 'Sin observacion' }}
                    </td>
                </tr>

                <template v-if="exchanges.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState
                            title="No hay canjes registrados"
                            description="Marca una falta y un dia trabajado como canje desde el calendario de asistencia."
                        />
                    </td>
                </template>
            </DataTable>

            <div v-if="exchanges.links?.length > 3" class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="exchanges.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
