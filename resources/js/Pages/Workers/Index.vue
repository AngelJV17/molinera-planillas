<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { BriefcaseBusiness, Database, Edit, Plus, Power, Users } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import PrimaryActionButton from '@/Components/Common/PrimaryActionButton.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';
import StatusFilter from '@/Components/Filters/StatusFilter.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';

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

const columns = [
    { key: 'worker', label: 'Trabajador' },
    { key: 'document', label: 'Documento' },
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
            search: search.value,
            status: status.value,
            work_shift_id: workShiftId.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, status, workShiftId, perPage], () => {
    applyFilters();
});

const toggleStatus = (worker) => {
    router.patch(
        route('workers.toggle-status', worker.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const fullName = (worker) => `${worker.first_name} ${worker.last_name}`;
const money = (amount) => `S/ ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <Head title="Trabajadores" />

    <AuthenticatedLayout title="Trabajadores">
        <section class="space-y-6">
            <PageHeader
                title="Gestión de trabajadores"
                description="Administra el expediente personal y laboral del personal de MOLICENTE."
            >
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
                    <select v-model="workShiftId" class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Todos los turnos</option>
                        <option v-for="shift in workShifts" :key="shift.id" :value="shift.id">
                            {{ shift.name }}
                        </option>
                    </select>
                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Trabajadores registrados</h2>
                    <p class="text-sm text-gray-500">Personal disponible para asistencia, planillas y boletas.</p>
                </div>

                <div class="flex items-center gap-4 rounded-2xl border border-primary/20 bg-white px-5 py-3 shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Database class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Total registros</p>
                        <p class="text-2xl font-black leading-none text-primary">
                            {{ workers.total ?? workers.data.length }}
                        </p>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns">
                <tr v-for="worker in workers.data" :key="worker.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <BriefcaseBusiness class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-bold text-gray-800">{{ fullName(worker) }}</p>
                                <p class="text-xs text-gray-500">{{ worker.employee_code }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <p class="font-mono text-xs font-bold text-gray-700">{{ worker.document_number }}</p>
                        <p class="text-xs text-gray-500">{{ worker.document_type?.name ?? 'Sin tipo' }}</p>
                    </td>

                    <td class="px-6 py-4">
                        <p class="font-semibold text-gray-700">{{ worker.position?.name ?? 'Sin cargo' }}</p>
                        <p class="text-xs text-gray-500">{{ worker.work_area?.name ?? 'Sin área' }}</p>
                    </td>

                    <td class="px-6 py-4 text-gray-600">
                        {{ worker.work_shift?.name ?? 'Sin turno' }}
                    </td>

                    <td class="px-6 py-4 font-bold text-gray-800">
                        {{ money(worker.base_salary) }}
                    </td>

                    <td class="px-6 py-4">
                        <StatusBadge :status="worker.status" />
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-end gap-2">
                            <Link
                                :href="route('workers.edit', worker.id)"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                                title="Editar"
                            >
                                <Edit class="h-4 w-4" />
                            </Link>

                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                                title="Cambiar estado"
                                @click="toggleStatus(worker)"
                            >
                                <Power class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>

                <template v-if="workers.data.length === 0" #empty>
                    <td colspan="7">
                        <EmptyState title="No se encontraron trabajadores" description="Modifica los filtros o registra un nuevo trabajador.">
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

            <div v-if="workers.links?.length > 3" class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="workers.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
