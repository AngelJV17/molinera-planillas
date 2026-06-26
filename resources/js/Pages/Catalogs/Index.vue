<script setup>
import { Head, router } from '@inertiajs/vue3';
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
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';
import TableActionButton from '@/Components/Table/TableActionButton.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';

import {
    BriefcaseBusiness,
    Building2,
    CreditCard,
    Database,
    Edit,
    HeartHandshake,
    IdCard,
    Landmark,
    ListChecks,
    Plus,
    Power,
    UserCheck,
    VenusAndMars,
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

let filterTimeout = null;

const activeGroup = computed(() => {
    return catalogGroups.find((group) => group.key === activeType.value);
});

const columns = [
    { key: 'catalog', label: 'Opción' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const applyFilters = () => {
    router.get(
        route('catalogs.index'),
        {
            type: activeType.value,
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

const changeGroup = (type) => {
    activeType.value = type;
    search.value = '';
    status.value = '';
    perPage.value = 10;

    applyFilters();
};

watch([search, status, perPage], () => {
    clearTimeout(filterTimeout);

    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

const toggleStatus = (catalog) => {
    router.patch(
        route('catalogs.toggle-status', catalog.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const catalogDescription = (catalog) => {
    return catalog.description ?? 'Sin descripción registrada.';
};

const catalogCode = (catalog) => {
    return catalog.code ?? 'Sin código';
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
                        :placeholder="`Buscar ${activeGroup?.label.toLowerCase()} por nombre, código o descripción...`" />

                    <StatusFilter v-model="status" />

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary :title="activeGroup?.label" :description="activeGroup?.description" label="Total registros"
                :total="catalogs.total ?? catalogs.data.length" :icon="Database" />

            <DataTable :columns="columns">
                <tr v-for="catalog in catalogs.data" :key="catalog.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell :icon="activeGroup?.icon" :title="catalog.name"
                            :subtitle="catalogDescription(catalog)">
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold text-primary">
                                    Código: {{ catalogCode(catalog) }}
                                </span>

                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">
                                    {{ activeGroup?.singular }}
                                </span>
                            </div>
                        </TableEntityCell>
                    </td>

                    <td class="px-6 py-4">
                        <StatusBadge :status="catalog.status" />
                    </td>

                    <td class="px-6 py-4">
                        <TableActions>
                            <TableActionButton :href="route('catalogs.edit', catalog.id)" :icon="Edit"
                                title="Editar opción" />

                            <TableActionButton :icon="Power" title="Cambiar estado" variant="danger"
                                @click="toggleStatus(catalog)" />
                        </TableActions>
                    </td>
                </tr>

                <template v-if="catalogs.data.length === 0" #empty>
                    <td :colspan="columns.length">
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
