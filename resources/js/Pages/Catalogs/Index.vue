<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import PrimaryActionButton from '@/Components/Common/PrimaryActionButton.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';
import FilterPanel from '@/Components/Filters/FilterPanel.vue';
import StatusFilter from '@/Components/Filters/StatusFilter.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';
import {
    Plus,
    Edit,
    Power,
    SlidersHorizontal,
    ListChecks,
    Database,
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

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 10);

const columns = [
    { key: 'type', label: 'Tipo' },
    { key: 'code', label: 'Código' },
    { key: 'name', label: 'Nombre' },
    { key: 'description', label: 'Descripción' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

/**
 * Aplica filtros al listado de catálogos.
 * Mantiene el estado visual de la página usando Inertia.
 */
const applyFilters = () => {
    router.get(
        route('catalogs.index'),
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

/**
 * Cambia el estado de un catálogo.
 * No se elimina el registro para conservar integridad histórica.
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
            <!-- Encabezado -->
            <PageHeader title="Gestión de Configuraciones Generales"
                description="Administra listas y opciones utilizadas por el sistema, como tipos de documento, regímenes pensionarios, estados de asistencia y estados de planilla.">
                <template #icon>
                    <ListChecks class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton :href="route('catalogs.create')">
                        <Plus class="h-4 w-4" />
                        Nuevo Registro
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <!-- Filtros -->
            <FilterPanel>
                <SearchInput v-model="search" placeholder="Buscar por tipo, código, nombre o descripción..." />

                <StatusFilter v-model="status" />

                <PerPageFilter v-model="perPage" />
            </FilterPanel>

            <!-- Resumen del listado -->
            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h2 class="text-lg font-black text-gray-900">
                        Configuraciones registradas
                    </h2>

                    <p class="text-sm text-gray-500">
                        Opciones globales utilizadas por los diferentes módulos del sistema.
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

            <!-- Tabla de catálogos -->
            <DataTable :columns="columns">
                <tr v-for="catalog in catalogs.data" :key="catalog.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <span class="rounded-lg bg-primary/10 px-3 py-1.5 text-xs font-black text-primary">
                            {{ catalog.type }}
                        </span>
                    </td>

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
                    <td colspan="6">
                        <EmptyState title="No se encontraron catálogos"
                            description="Intenta modificar los filtros o registra un nuevo catálogo.">
                            <template #action>

                                <PrimaryActionButton :href="route('catalogs.create')">
                                    <Plus class="h-4 w-4" />
                                    Nuevo catálogo
                                </PrimaryActionButton>

                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <!-- Paginación -->
            <div v-if="catalogs.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="catalogs.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
