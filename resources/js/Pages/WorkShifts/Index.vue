<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Clock3, Database, Edit, Moon, Plus, Power } from 'lucide-vue-next';

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
import { confirmStatusChange } from '@/Utils/alerts';

const props = defineProps({
    workShifts: {
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

const columns = [
    { key: 'name', label: 'Turno' },
    { key: 'schedule', label: 'Horario' },
    { key: 'daily_hours', label: 'Jornada' },
    { key: 'employees', label: 'Trabajadores' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const applyFilters = () => {
    router.get(
        route('work-shifts.index'),
        {
            search: search.value,
            status: status.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, status, perPage], () => {
    applyFilters();
});

const toggleStatus = async (workShift) => {
    const confirmed = await confirmStatusChange({
        title: '¿Cambiar estado del turno?',
        text: `Se actualizará el estado de ${workShift.name}.`,
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('work-shifts.toggle-status', workShift.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const formatTime = (time) => time?.slice(0, 5) ?? '--:--';
const formatHours = (hours) => `${Number(hours).toLocaleString('es-PE', { minimumFractionDigits: 2 })} h`;
</script>

<template>

    <Head title="Turnos" />

    <AuthenticatedLayout title="Turnos">
        <section class="space-y-6">
            <PageHeader title="Turnos laborales"
                description="Administra los horarios de trabajo utilizados para asistencia y planillas.">
                <template #icon>
                    <Clock3 class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton :href="route('work-shifts.create')">
                        <Plus class="h-4 w-4" />
                        Nuevo turno
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por nombre o descripción..." />
                    <StatusFilter v-model="status" />
                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Turnos registrados</h2>
                    <p class="text-sm text-gray-500">Horarios disponibles para asignación laboral.</p>
                </div>

                <div class="flex items-center gap-4 rounded-2xl border border-primary/20 bg-white px-5 py-3 shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Database class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">Total registros</p>
                        <p class="text-2xl font-black leading-none text-primary">
                            {{ workShifts.total ?? workShifts.data.length }}
                        </p>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns">
                <tr v-for="workShift in workShifts.data" :key="workShift.id"
                    class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-bold text-gray-800">{{ workShift.name }}</p>
                            <p v-if="workShift.break_start_time && workShift.break_end_time"
                                class="text-xs text-primary font-medium">
                                Almuerzo:
                                {{ formatTime(workShift.break_start_time) }}
                                -
                                {{ formatTime(workShift.break_end_time) }}
                            </p>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2 font-semibold text-gray-700">
                            {{ formatTime(workShift.start_time) }} - {{ formatTime(workShift.end_time) }}
                            <Moon v-if="workShift.crosses_midnight" class="h-4 w-4 text-secondary" />
                        </div>
                        <p class="text-xs text-gray-500">{{ workShift.tolerance_minutes }} min. tolerancia</p>
                    </td>

                    <td class="px-6 py-4 font-bold text-gray-800">
                        {{ formatHours(workShift.daily_hours) }}
                    </td>

                    <td class="px-6 py-4 text-gray-600">
                        {{ workShift.employees_count ?? 0 }}
                    </td>

                    <td class="px-6 py-4">
                        <StatusBadge :status="workShift.status" />
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-end gap-2">
                            <Link :href="route('work-shifts.edit', workShift.id)"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                                title="Editar">
                                <Edit class="h-4 w-4" />
                            </Link>

                            <button type="button"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                                title="Cambiar estado" @click="toggleStatus(workShift)">
                                <Power class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>

                <template v-if="workShifts.data.length === 0" #empty>
                    <td colspan="6">
                        <EmptyState title="No se encontraron turnos registrados"
                            description="Intenta modificar los filtros o registra un nuevo turno.">
                            <template #action>
                                <PrimaryActionButton :href="route('work-shifts.create')">
                                    <Plus class="h-4 w-4" />
                                    Nuevo turno
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div v-if="workShifts.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="workShifts.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
