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
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import StatusBadge from '@/Components/Table/StatusBadge.vue';
import TableActionButton from '@/Components/Table/TableActionButton.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';
import { confirmAction, confirmStatusChange } from '@/Utils/alerts';

import {
    CheckCircle2,
    Clipboard,
    Database,
    Edit,
    KeyRound,
    Plus,
    Power,
    ShieldCheck,
    UserCog,
    Users,
} from 'lucide-vue-next';

const props = defineProps({
    users: {
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
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 10);
const copied = ref(false);
const copyError = ref('');

let filterTimeout = null;

const columns = [
    { key: 'user', label: 'Usuario' },
    { key: 'role', label: 'Rol asignado' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const temporaryCredentials = computed(() => {
    return {
        username: page.props.flash?.temporary_username,
        password: page.props.flash?.temporary_password,
    };
});

const hasTemporaryCredentials = computed(() => {
    return Boolean(
        temporaryCredentials.value.username &&
        temporaryCredentials.value.password,
    );
});

const temporaryCredentialsText = computed(() => {
    return [
        'Credenciales temporales MOLICENTE',
        '',
        `Usuario: ${temporaryCredentials.value.username}`,
        `Contraseña temporal: ${temporaryCredentials.value.password}`,
        '',
        'Debe cambiar la contraseña al iniciar sesión por primera vez.',
    ].join('\n');
});

const applyFilters = () => {
    router.get(
        route('users.index'),
        {
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

watch([search, status, perPage], () => {
    clearTimeout(filterTimeout);

    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

const fallbackCopyText = (text) => {
    const textArea = document.createElement('textarea');

    textArea.value = text;
    textArea.setAttribute('readonly', '');
    textArea.style.position = 'fixed';
    textArea.style.top = '-9999px';
    textArea.style.left = '-9999px';

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    const successful = document.execCommand('copy');

    document.body.removeChild(textArea);

    return successful;
};

const copyTemporaryCredentials = async () => {
    if (!hasTemporaryCredentials.value) {
        return;
    }

    copyError.value = '';

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(temporaryCredentialsText.value);
        } else {
            const copiedWithFallback = fallbackCopyText(temporaryCredentialsText.value);

            if (!copiedWithFallback) {
                throw new Error('No se pudo copiar con el método alternativo.');
            }
        }

        copied.value = true;

        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch (error) {
        copyError.value = 'No se pudo copiar automáticamente. Selecciona el texto manualmente.';
    }
};

const toggleStatus = async (user) => {
    const confirmed = await confirmStatusChange({
        title: '¿Cambiar estado del usuario?',
        text: `Se actualizará el estado de ${user.name}.`,
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('users.toggle-status', user.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const resetPassword = async (user) => {
    const confirmed = await confirmAction({
        title: '¿Restablecer contraseña?',
        text: `Se generará una nueva contraseña temporal para ${user.name}.`,
        icon: 'warning',
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'Cancelar',
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('users.reset-password', user.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const userSubtitle = (user) => {
    return user.email ?? 'Sin correo registrado';
};

const userUsername = (user) => {
    return user.username ? `Usuario: ${user.username}` : 'Sin usuario registrado';
};

const userRoles = (user) => {
    if (!user.roles || user.roles.length === 0) {
        return 'Sin rol asignado';
    }

    return user.roles.map((role) => role.name).join(', ');
};
</script>

<template>

    <Head title="Usuarios" />

    <AuthenticatedLayout title="Usuarios">
        <section class="space-y-6">
            <PageHeader title="Gestión de usuarios"
                description="Administra las cuentas de acceso al sistema y asigna roles según las responsabilidades de cada usuario.">
                <template #icon>
                    <Users class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton :href="route('users.create')">
                        <Plus class="h-4 w-4" />
                        Nuevo usuario
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por nombre, usuario o correo..." />

                    <StatusFilter v-model="status" />

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <div v-if="hasTemporaryCredentials" class="rounded-3xl border border-primary/20 bg-primary/5 p-5 shadow-lg">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <KeyRound class="h-6 w-6" />
                        </div>

                        <div>
                            <h3 class="text-base font-black text-gray-900">
                                Credenciales temporales generadas
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                Copia estas credenciales y entrégaselas al usuario. Por seguridad, no volverán a
                                mostrarse.
                            </p>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                        Usuario
                                    </p>

                                    <p class="mt-1 font-mono text-sm font-black text-gray-800">
                                        {{ temporaryCredentials.username }}
                                    </p>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                        Contraseña temporal
                                    </p>

                                    <p class="mt-1 font-mono text-sm font-black text-primary">
                                        {{ temporaryCredentials.password }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                    Texto listo para copiar
                                </p>

                                <pre
                                    class="mt-2 whitespace-pre-wrap break-words font-mono text-xs font-semibold text-gray-700">{{
                        temporaryCredentialsText }}</pre>
                            </div>

                            <p v-if="copyError"
                                class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800">
                                {{ copyError }}
                            </p>
                        </div>
                    </div>

                    <button type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg transition-all duration-300"
                        :class="[
                            copied
                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                : 'bg-primary hover:bg-primary-dark'
                        ]" @click="copyTemporaryCredentials">
                        <component :is="copied ? CheckCircle2 : Clipboard"
                            class="h-4 w-4 transition-transform duration-300"
                            :class="copied ? 'scale-110' : 'scale-100'" />

                        {{ copied ? 'Credenciales copiadas' : 'Copiar credenciales' }}
                    </button>
                </div>
            </div>

            <ListSummary title="Usuarios registrados"
                description="Cuentas disponibles para acceder al sistema MOLICENTE." label="Total registros"
                :total="users.total ?? users.data.length" :icon="Database" />

            <DataTable :columns="columns">
                <tr v-for="user in users.data" :key="user.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell :icon="UserCog" :title="user.name" :subtitle="userSubtitle(user)"
                            :meta="userUsername(user)" />
                    </td>

                    <td class="px-6 py-4">
                        <TableEntityCell :icon="ShieldCheck" :title="userRoles(user)" subtitle="Perfil de acceso"
                            compact />
                    </td>

                    <td class="px-6 py-4">
                        <StatusBadge :status="user.status" />
                    </td>

                    <td class="px-6 py-4">
                        <TableActions>
                            <TableActionButton :href="route('users.edit', user.id)" :icon="Edit"
                                title="Editar usuario" />

                            <TableActionButton :icon="KeyRound" title="Restablecer contraseña" variant="warning"
                                @click="resetPassword(user)" />

                            <TableActionButton :icon="Power" title="Cambiar estado" variant="danger"
                                @click="toggleStatus(user)" />
                        </TableActions>
                    </td>
                </tr>

                <template v-if="users.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState title="No se encontraron usuarios"
                            description="Modifica los filtros o registra un nuevo usuario para el sistema.">
                            <template #action>
                                <PrimaryActionButton :href="route('users.create')">
                                    <Plus class="h-4 w-4" />
                                    Nuevo usuario
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div v-if="users.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="users.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
