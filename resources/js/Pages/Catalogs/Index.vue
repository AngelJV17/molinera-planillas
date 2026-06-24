<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import PrimaryActionButton from '@/Components/Common/PrimaryActionButton.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import StatusFilter from '@/Components/Filters/StatusFilter.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';

import {
    Plus,
    Edit,
    Power,
    ListChecks,
    Database,
    IdCard,
    VenusAndMars,
    HeartHandshake,
    Building2,
    BriefcaseBusiness,
    UserCheck,
    Landmark,
    CreditCard,
} from 'lucide-vue-next';

const props = defineProps({
    catalogs: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

/**
 * Categorías visibles para el usuario.
 * Internamente se sigue guardando el valor técnico en catalogs.type.
 */
const catalogGroups = [
    {
        key: 'DOCUMENT_TYPE',
        label: 'Tipos de documento',
        singular: 'Tipo de documento',
        description: 'DNI, Carné de Extranjería y otros documentos.',
        icon: IdCard,
    },
    {
        key: 'GENDER',
        label: 'Géneros',
        singular: 'Género',
        description: 'Opciones de género para los trabajadores.',
        icon: VenusAndMars,
    },
    {
        key: 'MARITAL_STATUS',
        label: 'Estados civiles',
        singular: 'Estado civil',
        description: 'Soltero, casado, conviviente y otros.',
        icon: HeartHandshake,
    },
    {
        key: 'WORK_AREA',
        label: 'Áreas de trabajo',
        singular: 'Área de trabajo',
        description: 'Áreas internas de la empresa.',
        icon: Building2,
    },
    {
        key: 'POSITION',
        label: 'Cargos',
        singular: 'Cargo',
        description: 'Cargos asignados a los trabajadores.',
        icon: BriefcaseBusiness,
    },
    {
        key: 'WORKER_STATUS',
        label: 'Estados laborales',
        singular: 'Estado laboral',
        description: 'Estados usados para controlar trabajadores.',
        icon: UserCheck,
    },
    {
        key: 'PENSION_SYSTEM',
        label: 'Sistemas pensionarios',
        singular: 'Sistema pensionario',
        description: 'ONP, AFP u otros sistemas.',
        icon: Landmark,
    },
    {
        key: 'ACCOUNT_TYPE',
        label: 'Tipos de cuenta',
        singular: 'Tipo de cuenta',
        description: 'Cuenta de ahorros, corriente u otras.',
        icon: CreditCard,
    },
];

const activeType = ref(props.filters.type ?? 'DOCUMENT_TYPE');
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 10);

const activeGroup = computed(() => {
    return catalogGroups.find((group) => group.key === activeType.value);
});

const columns = [
    { key: 'code', label: 'Código' },
    { key: 'name', label: 'Nombre' },
    { key: 'description', label: 'Descripción' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

/**
 * Aplica filtros al listado respetando la categoría activa.
 */
const applyFilters = () => {
    router.get(
        route('catalogs.index'),
        {
            type: activeType.value,
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

/**
 * Cambia la categoría visible.
 */
const changeGroup = (type) => {
    activeType.value = type;
    search.value = '';
    status.value = '';
    perPage.value = 10;

    applyFilters();
};

watch([search, status, perPage], () => {
    applyFilters();
});

/**
 * Cambia el estado de un catálogo.
 */
const toggleStatus = (catalog) => {
    router.patch(
        route('catalogs.toggle-status', catalog.id),
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>

    <Head title="Configuraciones Generales" />

    <AuthenticatedLayout title="Configuraciones Generales">
        <section class="space-y-6">
            <PageHeader title="Configuraciones Generales"
                description="Administra opciones reutilizables del sistema mediante categorías claras para el usuario.">
                <template #icon>
                    <ListChecks class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton :href="route('catalogs.create', { type: activeType })">
                        <Plus class="h-4 w-4" />
                        Nuevo {{ activeGroup?.singular }}
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <!-- Categorías -->
            <div class="rounded-3xl border border-slate-300 bg-white p-4 shadow-lg">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <button v-for="group in catalogGroups" :key="group.key" type="button"
                        class="flex h-24 items-center gap-4 rounded-2xl border px-4 py-4 text-left transition" :class="[
                            activeType === group.key
                                ? 'border-primary bg-primary/10 text-primary shadow-sm'
                                : 'border-slate-200 bg-slate-50 text-gray-600 hover:border-primary/30 hover:bg-primary/5'
                        ]" @click="changeGroup(group.key)">
                        <div class="flex h-12 w-12 min-w-[48px] max-w-[48px] shrink-0 items-center justify-center rounded-xl"
                            :class="[
                                activeType === group.key
                                    ? 'bg-primary text-white'
                                    : 'bg-white text-primary'
                            ]">
                            <component :is="group.icon" class="h-5 w-5 shrink-0" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-black">
                                {{ group.label }}
                            </p>

                            <p class="line-clamp-2 text-xs leading-snug text-gray-500">
                                {{ group.description }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search"
                        :placeholder="`Buscar ${activeGroup?.label.toLowerCase()} por código, nombre o descripción...`" />

                    <StatusFilter v-model="status" />

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h2 class="text-lg font-black text-gray-900">
                        {{ activeGroup?.label }}
                    </h2>

                    <p class="text-sm text-gray-500">
                        {{ activeGroup?.description }}
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
                            {{ catalogs.total ?? catalogs.data.length }}
                        </p>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns">
                <tr v-for="catalog in catalogs.data" :key="catalog.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <span class="font-mono text-xs font-bold text-gray-700">
                            {{ catalog.code }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-bold text-gray-800">
                        {{ catalog.name }}
                    </td>

                    <td class="px-6 py-4 text-gray-600">
                        {{ catalog.description ?? '—' }}
                    </td>

                    <td class="px-6 py-4">
                        <StatusBadge :status="catalog.status" />
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex justify-end gap-2">
                            <Link :href="route('catalogs.edit', catalog.id)"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                                title="Editar">
                                <Edit class="h-4 w-4" />
                            </Link>

                            <button type="button"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                                title="Cambiar estado" @click="toggleStatus(catalog)">
                                <Power class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>

                <template v-if="catalogs.data.length === 0" #empty>
                    <td colspan="5">
                        <EmptyState :title="`No se encontraron ${activeGroup?.label.toLowerCase()}`"
                            description="Intenta modificar los filtros o registra una nueva opción.">
                            <template #action>
                                <PrimaryActionButton :href="route('catalogs.create', { type: activeType })">
                                    <Plus class="h-4 w-4" />
                                    Nuevo {{ activeGroup?.singular }}
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div v-if="catalogs.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="catalogs.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
