<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Download, Eye, ReceiptText, Send } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import DataTable from '@/Components/Table/DataTable.vue';

const slips = ref([
    { id: 1, trabajador: 'Juan Pérez Huamán', periodo: 'Junio 2026', neto: 1468, estado: 'Emitida' },
    { id: 2, trabajador: 'María Quispe Rojas', periodo: 'Junio 2026', neto: 1565, estado: 'Pendiente' },
    { id: 3, trabajador: 'Carlos Mendoza Salas', periodo: 'Junio 2026', neto: 2393, estado: 'Emitida' },
]);

const columns = [
    { key: 'trabajador', label: 'Trabajador' },
    { key: 'periodo', label: 'Periodo' },
    { key: 'neto', label: 'Neto a pagar' },
    { key: 'estado', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const money = (amount) => `S/ ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <Head title="Boletas" />

    <AuthenticatedLayout title="Boletas">
        <section class="space-y-6">
            <PageHeader title="Boletas de pago" description="Consulta, descarga y simula el envío de boletas mensuales.">
                <template #icon>
                    <ReceiptText class="h-7 w-7" />
                </template>

                <template #actions>
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-primary-dark">
                        <Send class="h-4 w-4" />
                        Enviar boletas
                    </button>
                </template>
            </PageHeader>

            <section class="space-y-4">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Boletas generadas</h2>
                    <p class="text-sm text-gray-500">Listado de boletas por trabajador y periodo.</p>
                </div>

                <DataTable :columns="columns">
                    <tr v-for="slip in slips" :key="slip.id" class="text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4 font-bold text-gray-800">{{ slip.trabajador }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ slip.periodo }}</td>
                        <td class="px-6 py-4 font-black text-primary-dark">{{ money(slip.neto) }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold" :class="slip.estado === 'Emitida' ? 'bg-primary/15 text-primary' : 'bg-secondary/15 text-secondary'">
                                {{ slip.estado }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <button type="button" class="rounded-xl border border-slate-300 p-2 text-gray-700 hover:bg-primary/10 hover:text-primary">
                                    <Eye class="h-4 w-4" />
                                </button>
                                <button type="button" class="rounded-xl border border-slate-300 p-2 text-gray-700 hover:bg-primary/10 hover:text-primary">
                                    <Download class="h-4 w-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </DataTable>
            </section>
        </section>
    </AuthenticatedLayout>
</template>
