<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

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

import {
    BriefcaseBusiness,
    Database,
    Edit,
    Plus,
    Power,
    Users,
} from 'lucide-vue-next';

const props = defineProps({
    workers: {
        type: Object,
        required: true,
    },

    workShifts: {
        type: Array,
        default: () => [],
    },

    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const workShiftId = ref(props.filters.work_shift_id ?? '');
const perPage = ref(props.filters.per_page ?? 10);

let filterTimeout = null;

const columns = [
    { key: 'worker', label: 'Trabajador' },
    { key: 'position', label: 'Cargo / Área' },
    { key: 'shift', label: 'Turno' },
    { key: 'salary', label: 'Sueldo' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const applyFilters = () => {
    router.get(
        route('workers.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            work_shift_id: workShiftId.value || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, status, workShiftId, perPage], () => {
    clearTimeout(filterTimeout);

    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

const toggleStatus = async (worker) => {
    const confirmed = await confirmStatusChange({
        title: '¿Cambiar estado del trabajador?',
        text: `Se actualizará el estado de ${fullName(worker)}.`,
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('workers.toggle-status', worker.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const fullName = (worker) => {
    return `${worker.first_name} ${worker.last_name}`;
};

const workerEmail = (worker) => {
    return worker.email ?? 'Sin correo registrado';
};

const workerCode = (worker) => {
    return worker.employee_code ?? 'Sin código';
};

const workerDocument = (worker) => {
    const type = worker.document_type?.name ?? 'Documento';
    const number = worker.document_number ?? 'Sin número';

    return `${type}: ${number}`;
};

const workerSubtitle = (worker) => {
    const code = worker.employee_code ?? 'Sin código';
    const email = worker.email ?? 'Sin correo';

    return `${code} · ${email}`;
};

const documentLabel = (worker) => {
    return worker.document_type?.name ?? 'Sin tipo';
};

const positionLabel = (worker) => {
    return worker.position?.name ?? 'Sin cargo';
};

const areaLabel = (worker) => {
    return worker.work_area?.name ?? 'Sin área';
};

const shiftLabel = (worker) => {
    return worker.work_shift?.name ?? 'Sin turno';
};

const money = (amount) => {
    return `S/ ${Number(amount ?? 0).toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};
</script>

<template>

    <Head title="Trabajadores" />

    <AuthenticatedLayout title="Trabajadores">
        <section class="space-y-6">
            <PageHeader title="Gestión de trabajadores"
                description="Administra el expediente personal y laboral del personal de MOLICENTE.">
                <template #icon>
                    <Users class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton :href="route('workers.create')">
                        <Plus class="h-4 w-4" />
                        Nuevo trabajador
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por código, documento, nombres o correo..." />

                    <StatusFilter v-model="status" />

                    <select v-model="workShiftId"
                        class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">
                            Todos los turnos
                        </option>

                        <option v-for="shift in workShifts" :key="shift.id" :value="shift.id">
                            {{ shift.name }}
                        </option>
                    </select>

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary title="Trabajadores registrados"
                description="Personal disponible para asistencia, planillas y boletas." label="Total registros"
                :total="workers.total ?? workers.data.length" :icon="Database" />

            <DataTable :columns="columns">
                <tr v-for="worker in workers.data" :key="worker.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell :icon="BriefcaseBusiness" :title="fullName(worker)"
                            :subtitle="workerEmail(worker)">
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold text-primary">
                                    {{ workerCode(worker) }}
                                </span>

                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">
                                    {{ workerDocument(worker) }}
                                </span>
                            </div>
                        </TableEntityCell>
                    </td>

                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-700">
                            {{ positionLabel(worker) }}
                        </p>

                        <p class="text-xs text-gray-500">
                            {{ areaLabel(worker) }}
                        </p>
                    </td>

                    <td class="px-6 py-4 text-gray-600">
                        {{ shiftLabel(worker) }}
                    </td>

                    <td class="px-6 py-4 font-bold text-gray-800">
                        {{ money(worker.base_salary) }}
                    </td>

                    <td class="px-6 py-4">
                        <StatusBadge :status="worker.status" />
                    </td>

                    <td class="px-6 py-4">
                        <TableActions>
                            <TableActionButton :href="route('workers.edit', worker.id)" :icon="Edit"
                                title="Editar trabajador" />

                            <TableActionButton :icon="Power" title="Cambiar estado" variant="danger"
                                @click="toggleStatus(worker)" />
                        </TableActions>
                    </td>
                </tr>

                <template v-if="workers.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState title="No se encontraron trabajadores"
                            description="Modifica los filtros o registra un nuevo trabajador.">
                            <template #action>
                                <PrimaryActionButton :href="route('workers.create')">
                                    <Plus class="h-4 w-4" />
                                    Nuevo trabajador
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div v-if="workers.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="workers.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
