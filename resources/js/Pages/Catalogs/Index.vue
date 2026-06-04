<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';
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

    <Head title="Catálogos" />

    <AuthenticatedLayout title="Catálogos">
        <section class="space-y-6">
            <!-- Encabezado principal del módulo -->
            <div class="overflow-hidden rounded-3xl border border-primary/20 bg-white shadow-lg">
                <div class="h-1.5 bg-primary"></div>

                <div class="flex flex-col justify-between gap-5 p-6 lg:flex-row lg:items-center">
                    <div class="flex gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                            <ListChecks class="h-7 w-7" />
                        </div>

                        <div>
                            <h1 class="text-2xl font-black text-gray-900">
                                Gestión de catálogos
                            </h1>

                            <p class="mt-1 max-w-3xl text-sm leading-relaxed text-gray-600">
                                Administra opciones reutilizables como tipos de documento,
                                regímenes pensionarios, estados de asistencia y estados de planilla.
                            </p>
                        </div>
                    </div>

                    <Link :href="route('catalogs.create')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-primary-dark hover:shadow-lg">
                        <Plus class="h-4 w-4" />
                        Nuevo catálogo
                    </Link>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-3xl border border-slate-300 bg-white shadow-lg">
                <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-100 px-6 py-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-primary">
                        <SlidersHorizontal class="h-5 w-5" />
                    </div>

                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wide text-gray-800">
                            Filtros de búsqueda
                        </h2>
                        <p class="text-xs text-gray-500">
                            Refina los resultados del listado.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-3">
                    <SearchInput v-model="search" placeholder="Buscar por tipo, código, nombre o descripción..." />

                    <select v-model="status"
                        class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Todos los estados</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>

                    <select v-model="perPage"
                        class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                    </select>
                </div>
            </div>

            <!-- Resumen del listado -->
            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h2 class="text-lg font-black text-gray-900">
                        Catálogos registrados
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
                    <td colspan="6" class="px-6 py-14 text-center text-sm font-medium text-gray-500">
                        No se encontraron catálogos con los filtros aplicados.
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
