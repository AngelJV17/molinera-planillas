<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Building2, Database, Edit, Plus, Power } from 'lucide-vue-next';

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
    banks: {
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
    { key: 'name', label: 'Banco' },
    { key: 'code', label: 'Código' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const applyFilters = () => {
    router.get(
        route('banks.index'),
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

const toggleStatus = async (bank) => {
    const confirmed = await confirmStatusChange({
        title: '¿Cambiar estado del banco?',
        text: `Se actualizará el estado de ${bank.name}.`,
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('banks.toggle-status', bank.id),
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="Bancos" />

    <AuthenticatedLayout title="Bancos">
        <section class="space-y-6">
            <PageHeader
                title="Bancos"
                description="Administra las entidades financieras utilizadas por los trabajadores."
            >
                <template #icon>
                    <Building2 class="h-7 w-7" />
                </template>

                <template #actions>
                    <PrimaryActionButton :href="route('banks.create')">
                        <Plus class="h-4 w-4" />
                        Nuevo registro
                    </PrimaryActionButton>
                </template>
            </PageHeader>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por nombre o código..." />
                    <StatusFilter v-model="status" />
                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h2 class="text-lg font-black text-gray-900">
                        Bancos registrados
                    </h2>

                    <p class="text-sm text-gray-500">
                        Entidades financieras utilizadas por los trabajadores.
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
                            {{ banks.total ?? banks.data.length }}
                        </p>
                    </div>
                </div>
            </div>

            <DataTable :columns="columns">
                <tr v-for="bank in banks.data" :key="bank.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4 font-semibold">
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
                            <Link
                                :href="route('banks.edit', bank.id)"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary"
                                title="Editar"
                            >
                                <Edit class="h-4 w-4" />
                            </Link>

                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-danger hover:bg-danger/10 hover:text-danger"
                                title="Cambiar estado"
                                @click="toggleStatus(bank)"
                            >
                                <Power class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>

                <template v-if="banks.data.length === 0" #empty>
                    <td colspan="4">
                        <EmptyState
                            title="No se encontraron bancos registrados"
                            description="Intenta modificar los filtros o registra un nuevo banco."
                        >
                            <template #action>
                                <PrimaryActionButton :href="route('banks.create')">
                                    <Plus class="h-4 w-4" />
                                    Nuevo banco
                                </PrimaryActionButton>
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <div
                v-if="banks.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg"
            >
                <Pagination :links="banks.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
