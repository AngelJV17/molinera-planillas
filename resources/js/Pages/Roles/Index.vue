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

import DataTable from '@/Components/Table/DataTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import TableActionButton from '@/Components/Table/TableActionButton.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';

import {
    Database,
    Edit,
    KeyRound,
    Plus,
    ShieldCheck,
    UsersRound,
} from 'lucide-vue-next';

const props = defineProps({
    roles: {
        type: Object,
        required: true,
    },

    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 10);

let filterTimeout = null;
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permission) => permissions.value.includes(permission);
const canManageRoles = computed(() => can('roles.edit') || can('roles.assign-permissions'));

const baseColumns = [
    { key: 'role', label: 'Rol' },
    { key: 'description', label: 'Descripción' },
    { key: 'permissions', label: 'Permisos' },
    { key: 'users', label: 'Usuarios' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const columns = computed(() => {
    return canManageRoles.value
        ? baseColumns
        : baseColumns.filter((column) => column.key !== 'actions');
});

const roleDescriptions = {
    'Super Admin': 'Acceso total a todos los módulos y configuraciones del sistema.',
    Administrador: 'Gestiona operaciones principales como trabajadores, asistencia y configuración.',
    Gerente: 'Revisa información, reportes y aprueba procesos de planilla.',
    Contador: 'Gestiona planillas, boletas y reportes contables.',
    RRHH: 'Administra trabajadores, asistencia y datos laborales.',
    Trabajador: 'Consulta información personal y boletas de pago.',
};

const protectedRoles = [
    'Super Admin',
    'Administrador',
];

const roleDescription = (role) => {
    return roleDescriptions[role.name] ?? 'Rol personalizado configurado para el sistema.';
};

const isProtectedRole = (role) => {
    return protectedRoles.includes(role.name);
};

const applyFilters = () => {
    router.get(
        route('roles.index'),
        {
            search: search.value || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([search, perPage], () => {
    clearTimeout(filterTimeout);

    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});
</script>

<template>

    <Head title="Roles y Permisos" />

    <AuthenticatedLayout title="Roles y Permisos">
        <section class="space-y-6">
            <PageHeader title="Roles y Permisos"
                description="Administra los perfiles de acceso y permisos asignados a los usuarios del sistema MOLICENTE.">
                <template #icon>
                    <ShieldCheck class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton v-if="can('roles.create')" :href="route('roles.create')">
                        <Plus class="h-4 w-4" />
                        Nuevo rol
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por nombre de rol..." />

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary title="Roles registrados"
                description="Perfiles disponibles para controlar el acceso a módulos, acciones y procesos del sistema."
                label="Total registros" :total="roles.total ?? roles.data.length" :icon="Database" />

            <DataTable :columns="columns">
                <tr v-for="role in roles.data" :key="role.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell :icon="ShieldCheck" :title="role.name" subtitle="Rol de seguridad del sistema">
                            <template #badge>
                                <span v-if="isProtectedRole(role)"
                                    class="rounded-full border border-primary/20 bg-primary/10 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-primary">
                                    Protegido
                                </span>
                            </template>
                        </TableEntityCell>
                    </td>

                    <td class="px-6 py-4 text-gray-600">
                        {{ roleDescription(role) }}
                    </td>

                    <td class="px-6 py-4">
                        <TableEntityCell :icon="KeyRound" :title="role.permissions_count ?? 0"
                            subtitle="permisos asignados" compact />
                    </td>

                    <td class="px-6 py-4">
                        <TableEntityCell :icon="UsersRound" :title="role.users_count ?? 0"
                            subtitle="usuarios vinculados" compact />
                    </td>

                    <td v-if="canManageRoles" class="px-6 py-4">
                        <TableActions>
                            <TableActionButton :href="route('roles.edit', role.id)" :icon="Edit" title="Editar rol" />
                        </TableActions>
                    </td>
                </tr>

                <template v-if="roles.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState title="No se encontraron roles"
                            description="Modifica los filtros o registra un nuevo rol para el sistema.">
                            <template #action>
                                <PrimaryActionButton v-if="can('roles.create')" :href="route('roles.create')">
                                    <Plus class="h-4 w-4" />
                                    Nuevo rol
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div v-if="roles.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="roles.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
