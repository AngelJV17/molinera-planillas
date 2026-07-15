<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Database, Edit, Plus, Power, SlidersHorizontal } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import ListSummary from '@/Components/Common/ListSummary.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import PrimaryActionButton from '@/Components/Common/PrimaryActionButton.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';
import StatusFilter from '@/Components/Filters/StatusFilter.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';
import TableActionButton from '@/Components/Table/TableActionButton.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';
import { confirmStatusChange } from '@/Utils/alerts';
import { formatDate } from '@/Utils/dates';

const props = defineProps({
    parameters: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 10);
let filterTimeout = null;

const columns = [
    { key: 'parameter', label: 'Parametro' },
    { key: 'value', label: 'Valor' },
    { key: 'effective_from', label: 'Vigencia' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const applyFilters = () => {
    router.get(
        route('payroll-parameters.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, status, perPage], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => applyFilters(), 350);
});

const formatValue = (value) => {
    return Number(value ?? 0).toLocaleString('es-PE', {
        minimumFractionDigits: 4,
        maximumFractionDigits: 4,
    });
};

const toggleStatus = async (parameter) => {
    const confirmed = await confirmStatusChange({
        title: 'Cambiar estado del parametro',
        text: `Se actualizara el estado de ${parameter.name}.`,
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('payroll-parameters.toggle-status', parameter.id),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Parametros de planilla" />

    <AuthenticatedLayout title="Parametros de planilla">
        <section class="space-y-6">
            <PageHeader
                title="Parametros de planilla"
                description="Administra tasas y factores usados para calcular descuentos, aportes y horas extra."
            >
                <template #icon>
                    <SlidersHorizontal class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton :href="route('payroll-parameters.create')">
                        <Plus class="h-4 w-4" />
                        Nuevo parametro
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por codigo, nombre o descripcion..." />
                    <StatusFilter v-model="status" />
                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary
                title="Parametros registrados"
                description="Valores activos e historicos disponibles para el calculo de planillas."
                label="Total registros"
                :total="parameters.total ?? parameters.data.length"
                :icon="Database"
            />

            <DataTable :columns="columns">
                <tr v-for="parameter in parameters.data" :key="parameter.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell
                            :icon="SlidersHorizontal"
                            :title="parameter.name"
                            :subtitle="parameter.description ?? 'Sin descripcion registrada.'"
                        >
                            <div class="mt-2">
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold text-primary">
                                    {{ parameter.code }}
                                </span>
                            </div>
                        </TableEntityCell>
                    </td>

                    <td class="px-6 py-4 font-black text-primary-dark">
                        {{ formatValue(parameter.value) }}
                    </td>

                    <td class="px-6 py-4 text-gray-600">
                        {{ parameter.effective_from ? formatDate(parameter.effective_from) : 'Sin fecha' }}
                    </td>

                    <td class="px-6 py-4">
                        <StatusBadge :status="parameter.status" />
                    </td>

                    <td class="px-6 py-4">
                        <TableActions>
                            <TableActionButton
                                :href="route('payroll-parameters.edit', parameter.id)"
                                :icon="Edit"
                                title="Editar parametro"
                            />
                            <TableActionButton
                                :icon="Power"
                                title="Cambiar estado"
                                variant="danger"
                                @click="toggleStatus(parameter)"
                            />
                        </TableActions>
                    </td>
                </tr>

                <template v-if="parameters.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState
                            title="No se encontraron parametros"
                            description="Registra los valores necesarios para calcular planillas."
                        >
                            <template #action>
                                <PrimaryActionButton :href="route('payroll-parameters.create')">
                                    <Plus class="h-4 w-4" />
                                    Nuevo parametro
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div v-if="parameters.links?.length > 3" class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="parameters.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
