<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import PrimaryActionButton from '@/Components/Common/PrimaryActionButton.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';

import DataTable from '@/Components/Table/DataTable.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';

import StatusFilter from '@/Components/Filters/StatusFilter.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';

import {
    BriefcaseBusiness,
    Building2,
    Clock3,
    Database,
    Edit,
    Plus,
    Power,
    UsersRound,
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
    areas: {
        type: Array,
        default: () => [],
    },
    positions: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('banks');

const search = ref('');
const status = ref('');
const perPage = ref(10);
const currentPage = ref(1);

const tabs = [
    {
        key: 'banks',
        label: 'Bancos',
        description: 'Entidades financieras utilizadas por los trabajadores.',
        icon: Building2,
        createRoute: 'banks.create',
    },
    {
        key: 'workShifts',
        label: 'Turnos',
        description: 'Horarios laborales utilizados para asistencia y planillas.',
        icon: Clock3,
        createRoute: 'work-shifts.create',
    },
    {
        key: 'areas',
        label: 'Áreas',
        description: 'Áreas internas de la empresa.',
        icon: UsersRound,
        createRoute: null,
    },
    {
        key: 'positions',
        label: 'Cargos',
        description: 'Cargos laborales asignados a los trabajadores.',
        icon: BriefcaseBusiness,
        createRoute: null,
    },
];

const columns = computed(() => {
    if (activeTab.value === 'banks') {
        return [
            { key: 'name', label: 'Banco' },
            { key: 'code', label: 'Código' },
            { key: 'status', label: 'Estado' },
            { key: 'actions', label: 'Acciones', align: 'right' },
        ];
    }

    if (activeTab.value === 'workShifts') {
        return [
            { key: 'name', label: 'Turno' },
            { key: 'schedule', label: 'Horario' },
            { key: 'daily_hours', label: 'Jornada' },
            { key: 'status', label: 'Estado' },
            { key: 'actions', label: 'Acciones', align: 'right' },
        ];
    }

    return [
        { key: 'name', label: 'Nombre' },
        { key: 'description', label: 'Descripción' },
        { key: 'status', label: 'Estado' },
        { key: 'actions', label: 'Acciones', align: 'right' },
    ];
});

const currentTab = computed(() => {
    return tabs.find((tab) => tab.key === activeTab.value);
});

const currentRecords = computed(() => {
    if (activeTab.value === 'banks') return props.banks;
    if (activeTab.value === 'workShifts') return props.workShifts;
    if (activeTab.value === 'areas') return props.areas;
    if (activeTab.value === 'positions') return props.positions;

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
    if (page < 1 || page > totalPages.value) return;

    currentPage.value = page;
};

const formatTime = (time) => {
    return time ? time.slice(0, 5) : '--:--';
};

const formatHours = (hours) => {
    return `${Number(hours ?? 0).toLocaleString('es-PE', {
        minimumFractionDigits: 2,
    })} h`;
};

const toggleStatus = (record) => {
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

    <Head title="Estructura Organizacional" />

    <AuthenticatedLayout title="Estructura Organizacional">
        <section class="space-y-6">
            <PageHeader title="Estructura Organizacional"
                description="Administra datos base de la empresa como bancos, turnos, áreas y cargos.">
                <template #icon>
                    <BriefcaseBusiness class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton v-if="currentTab?.createRoute" :href="route(currentTab.createRoute)">
                        <Plus class="h-4 w-4" />
                        Nuevo registro
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <!-- Pestañas -->
            <div class="rounded-3xl border border-slate-300 bg-white p-4 shadow-lg">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
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

            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h2 class="text-lg font-black text-gray-900">
                        {{ currentTab?.label }} registrados
                    </h2>

                    <p class="text-sm text-gray-500">
                        {{ currentTab?.description }}
                    </p>
                </div>

                <div class="flex items-center gap-4 rounded-2xl border border-primary/20 bg-white px-5 py-3 shadow-md">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Database class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                            Total registros
                        </p>

                        <p class="text-2xl font-black leading-none text-primary">
                            {{ filteredRecords.length }}
                        </p>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns">
                <template v-if="activeTab === 'banks'">
                    <tr v-for="bank in visibleRecords" :key="bank.id" class="text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4 font-semibold text-gray-800">
                            {{ bank.name }}
                        </td>

                        <td class="px-6 py-4">
                            {{ bank.code ?? '-' }}
                        </td>

                        <td class="px-6 py-4">
                            <StatusBadge :status="bank.status" />
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Link :href="route('banks.edit', bank.id)"
                                    class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                                    title="Editar">
                                    <Edit class="h-4 w-4" />
                                </Link>

                                <button type="button"
                                    class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                                    title="Cambiar estado" @click="toggleStatus(bank)">
                                    <Power class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <template v-if="activeTab === 'workShifts'">
                    <tr v-for="shift in visibleRecords" :key="shift.id" class="text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-bold text-gray-800">
                                    {{ shift.name }}
                                </p>

                                <p v-if="shift.break_start_time && shift.break_end_time"
                                    class="text-xs font-medium text-primary">
                                    Almuerzo:
                                    {{ formatTime(shift.break_start_time) }}
                                    -
                                    {{ formatTime(shift.break_end_time) }}
                                </p>
                            </div>
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-700">
                            {{ formatTime(shift.start_time) }}
                            -
                            {{ formatTime(shift.end_time) }}
                        </td>

                        <td class="px-6 py-4 font-bold text-gray-800">
                            {{ formatHours(shift.daily_hours) }}
                        </td>

                        <td class="px-6 py-4">
                            <StatusBadge :status="shift.status" />
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Link :href="route('work-shifts.edit', shift.id)"
                                    class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                                    title="Editar">
                                    <Edit class="h-4 w-4" />
                                </Link>

                                <button type="button"
                                    class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                                    title="Cambiar estado" @click="toggleStatus(shift)">
                                    <Power class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <template v-if="activeTab === 'areas' || activeTab === 'positions'">
                    <tr v-for="record in visibleRecords" :key="record.id" class="text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4 font-semibold text-gray-800">
                            {{ record.name }}
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            {{ record.description ?? '-' }}
                        </td>

                        <td class="px-6 py-4">
                            <StatusBadge :status="record.status" />
                        </td>

                        <td class="px-6 py-4 text-right text-gray-400">
                            —
                        </td>
                    </tr>
                </template>

                <template v-if="visibleRecords.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState :title="`No se encontraron ${currentTab?.label.toLowerCase()}`"
                            description="Intenta modificar los filtros o registra un nuevo elemento.">
                            <template v-if="currentTab?.createRoute" #action>
                                <PrimaryActionButton :href="route(currentTab.createRoute)">
                                    <Plus class="h-4 w-4" />
                                    Nuevo registro
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div v-if="filteredRecords.length > Number(perPage)"
                class="flex flex-col gap-4 rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-gray-500">
                    Mostrando
                    <span class="font-bold text-gray-700">{{ visibleRecords.length }}</span>
                    de
                    <span class="font-bold text-gray-700">{{ filteredRecords.length }}</span>
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
