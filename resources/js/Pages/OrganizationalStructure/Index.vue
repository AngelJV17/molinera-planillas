<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import ListSummary from '@/Components/Common/ListSummary.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import PrimaryActionButton from '@/Components/Common/PrimaryActionButton.vue';

import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';
import StatusFilter from '@/Components/Filters/StatusFilter.vue';

import DataTable from '@/Components/Table/DataTable.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';
import TableActionButton from '@/Components/Table/TableActionButton.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';
import { confirmStatusChange } from '@/Utils/alerts';

import {
    Building2,
    Clock3,
    Database,
    Edit,
    Plus,
    Power,
    SlidersHorizontal,
} from 'lucide-vue-next';

const props = defineProps({
    banks: {
        type: Array,
        default: () => [],
    },

    workShifts: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('banks');
const page = usePage();

const search = ref('');
const status = ref('');
const perPage = ref(10);
const currentPage = ref(1);

const tabs = [
    {
        key: 'banks',
        label: 'Bancos',
        singular: 'banco',
        description: 'Entidades financieras utilizadas para pagos y cuentas de trabajadores.',
        icon: Building2,
        createRoute: 'banks.create',
        createPermission: 'banks.create',
    },
    {
        key: 'workShifts',
        label: 'Turnos',
        singular: 'turno',
        description: 'Horarios laborales utilizados para asistencia, planillas y control interno.',
        icon: Clock3,
        createRoute: 'work-shifts.create',
        createPermission: 'work-shifts.create',
    },
];

const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permission) => permissions.value.includes(permission);
const canCreateCurrentTab = computed(() => currentTab.value?.createPermission && can(currentTab.value.createPermission));
const canManageBanks = computed(() => can('banks.edit') || can('banks.toggle-status'));
const canManageWorkShifts = computed(() => can('work-shifts.edit') || can('work-shifts.toggle-status'));

const columns = computed(() => {
    if (activeTab.value === 'banks') {
        const bankColumns = [
            { key: 'entity', label: 'Banco' },
            { key: 'status', label: 'Estado' },
            { key: 'actions', label: 'Acciones', align: 'right' },
        ];

        return canManageBanks.value
            ? bankColumns
            : bankColumns.filter((column) => column.key !== 'actions');
    }

    const workShiftColumns = [
        { key: 'entity', label: 'Turno' },
        { key: 'schedule', label: 'Horario / Jornada' },
        { key: 'status', label: 'Estado' },
        { key: 'actions', label: 'Acciones', align: 'right' },
    ];

    return canManageWorkShifts.value
        ? workShiftColumns
        : workShiftColumns.filter((column) => column.key !== 'actions');
});

const currentTab = computed(() => {
    return tabs.find((tab) => tab.key === activeTab.value);
});

const currentRecords = computed(() => {
    if (activeTab.value === 'banks') {
        return props.banks;
    }

    if (activeTab.value === 'workShifts') {
        return props.workShifts;
    }

    return [];
});

const filteredRecords = computed(() => {
    return currentRecords.value.filter((record) => {
        const searchText = search.value.toLowerCase();

        const matchesSearch =
            !search.value ||
            record.name?.toLowerCase().includes(searchText) ||
            record.code?.toLowerCase().includes(searchText) ||
            record.description?.toLowerCase().includes(searchText);

        const matchesStatus =
            status.value === '' ||
            String(Number(record.status)) === String(status.value);

        return matchesSearch && matchesStatus;
    });
});

const totalPages = computed(() => {
    return Math.ceil(filteredRecords.value.length / Number(perPage.value)) || 1;
});

const visibleRecords = computed(() => {
    const start = (currentPage.value - 1) * Number(perPage.value);
    const end = start + Number(perPage.value);

    return filteredRecords.value.slice(start, end);
});

const changeTab = (tabKey) => {
    activeTab.value = tabKey;
    search.value = '';
    status.value = '';
    perPage.value = 10;
    currentPage.value = 1;
};

watch([search, status, perPage], () => {
    currentPage.value = 1;
});

const goToPage = (page) => {
    if (page < 1 || page > totalPages.value) {
        return;
    }

    currentPage.value = page;
};

const formatTime = (time) => {
    return time ? time.slice(0, 5) : '--:--';
};

const formatHours = (hours) => {
    return `${Number(hours ?? 0).toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} h`;
};

const bankCode = (bank) => {
    return bank.code ? `Código: ${bank.code}` : 'Sin código registrado';
};

const shiftSchedule = (shift) => {
    return `${formatTime(shift.start_time)} - ${formatTime(shift.end_time)}`;
};

const shiftBreak = (shift) => {
    if (!shift.break_start_time || !shift.break_end_time) {
        return 'Sin horario de descanso registrado';
    }

    return `Descanso: ${formatTime(shift.break_start_time)} - ${formatTime(shift.break_end_time)}`;
};

const shiftTolerance = (shift) => {
    if (shift.tolerance_minutes === null || shift.tolerance_minutes === undefined) {
        return 'Sin tolerancia registrada';
    }

    return `Tolerancia: ${shift.tolerance_minutes} min`;
};

const toggleStatus = async (record) => {
    const confirmed = await confirmStatusChange({
        title: '¿Cambiar estado?',
        text: `Se actualizará el estado de ${record.name}.`,
    });

    if (!confirmed) {
        return;
    }

    if (activeTab.value === 'banks') {
        router.patch(route('banks.toggle-status', record.id), {}, {
            preserveScroll: true,
        });
    }

    if (activeTab.value === 'workShifts') {
        router.patch(route('work-shifts.toggle-status', record.id), {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>

    <Head title="Parámetros Laborales" />

    <AuthenticatedLayout title="Parámetros Laborales">
        <section class="space-y-6">
            <PageHeader title="Parámetros Laborales"
                description="Administra datos operativos del sistema como bancos y turnos de trabajo.">
                <template #icon>
                    <SlidersHorizontal class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton v-if="currentTab?.createRoute && canCreateCurrentTab" :href="route(currentTab.createRoute)">
                        <Plus class="h-4 w-4" />
                        Nuevo registro
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <!-- Pestañas -->
            <div class="rounded-3xl border border-slate-300 bg-white p-4 shadow-lg">
                <div class="grid gap-3 md:grid-cols-2">
                    <button v-for="tab in tabs" :key="tab.key" type="button"
                        class="flex h-24 items-center gap-4 rounded-2xl border px-4 py-4 text-left transition" :class="[
                            activeTab === tab.key
                                ? 'border-primary bg-primary/10 text-primary shadow-sm'
                                : 'border-slate-200 bg-slate-50 text-gray-600 hover:border-primary/30 hover:bg-primary/5'
                        ]" @click="changeTab(tab.key)">
                        <div class="flex h-12 w-12 min-w-[48px] max-w-[48px] shrink-0 items-center justify-center rounded-xl"
                            :class="[
                                activeTab === tab.key
                                    ? 'bg-primary text-white'
                                    : 'bg-white text-primary'
                            ]">
                            <component :is="tab.icon" class="h-5 w-5 shrink-0" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black">
                                {{ tab.label }}
                            </p>

                            <p class="line-clamp-2 text-xs leading-snug text-gray-500">
                                {{ tab.description }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por nombre, código o descripción..." />

                    <StatusFilter v-model="status" />

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary :title="`${currentTab?.label} registrados`" :description="currentTab?.description"
                label="Total registros" :total="filteredRecords.length" :icon="Database" />

            <DataTable :columns="columns">
                <!-- Bancos -->
                <template v-if="activeTab === 'banks'">
                    <tr v-for="bank in visibleRecords" :key="bank.id" class="text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4">
                            <TableEntityCell :icon="Building2" :title="bank.name" :subtitle="bankCode(bank)" />
                        </td>

                        <td class="px-6 py-4">
                            <StatusBadge :status="bank.status" />
                        </td>

                        <td v-if="canManageBanks" class="px-6 py-4">
                            <TableActions>
                                <TableActionButton v-if="can('banks.edit')" :href="route('banks.edit', bank.id)" :icon="Edit"
                                    title="Editar banco" />

                                <TableActionButton v-if="can('banks.toggle-status')" :icon="Power" title="Cambiar estado" variant="danger"
                                    @click="toggleStatus(bank)" />
                            </TableActions>
                        </td>
                    </tr>
                </template>

                <!-- Turnos -->
                <template v-if="activeTab === 'workShifts'">
                    <tr v-for="shift in visibleRecords" :key="shift.id" class="text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4">
                            <TableEntityCell :icon="Clock3" :title="shift.name" :subtitle="shiftBreak(shift)"
                                :meta="shiftTolerance(shift)" />
                        </td>

                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-800">
                                {{ shiftSchedule(shift) }}
                            </p>

                            <p class="text-xs font-medium text-gray-500">
                                Jornada: {{ formatHours(shift.daily_hours) }}
                            </p>
                        </td>

                        <td class="px-6 py-4">
                            <StatusBadge :status="shift.status" />
                        </td>

                        <td v-if="canManageWorkShifts" class="px-6 py-4">
                            <TableActions>
                                <TableActionButton v-if="can('work-shifts.edit')" :href="route('work-shifts.edit', shift.id)" :icon="Edit"
                                    title="Editar turno" />

                                <TableActionButton v-if="can('work-shifts.toggle-status')" :icon="Power" title="Cambiar estado" variant="danger"
                                    @click="toggleStatus(shift)" />
                            </TableActions>
                        </td>
                    </tr>
                </template>

                <template v-if="visibleRecords.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState :title="`No se encontraron ${currentTab?.label.toLowerCase()}`"
                            description="Intenta modificar los filtros o registra un nuevo elemento.">
                            <template v-if="currentTab?.createRoute && canCreateCurrentTab" #action>
                                <PrimaryActionButton :href="route(currentTab.createRoute)">
                                    <Plus class="h-4 w-4" />
                                    Nuevo registro
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <!-- Paginación local -->
            <div v-if="filteredRecords.length > Number(perPage)"
                class="flex flex-col gap-4 rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-gray-500">
                    Mostrando
                    <span class="font-bold text-gray-700">
                        {{ visibleRecords.length }}
                    </span>
                    de
                    <span class="font-bold text-gray-700">
                        {{ filteredRecords.length }}
                    </span>
                    registros
                </p>

                <div class="flex items-center gap-2">
                    <button type="button"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">
                        Anterior
                    </button>

                    <span class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-gray-700">
                        Página {{ currentPage }} de {{ totalPages }}
                    </span>

                    <button type="button"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">
                        Siguiente
                    </button>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
