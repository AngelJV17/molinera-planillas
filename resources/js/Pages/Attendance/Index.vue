<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import {
    CalendarDays,
    CheckCircle2,
    Clock,
    Save,
    TriangleAlert,
} from 'lucide-vue-next';

const attendance = ref([
    { id: 1, trabajador: 'Juan Perez Huaman', fecha: '2026-06-01', estado: 'Asistio', entrada: '07:55', salida: '17:10', horasExtras: 1, horasCanjeables: 0 },
    { id: 2, trabajador: 'Maria Quispe Rojas', fecha: '2026-06-01', estado: 'Pendiente', entrada: '', salida: '', horasExtras: 0, horasCanjeables: 0 },
    { id: 3, trabajador: 'Carlos Mendoza Salas', fecha: '2026-06-01', estado: 'Falta', entrada: '', salida: '', horasExtras: 0, horasCanjeables: 0 },
    { id: 4, trabajador: 'Rosa Campos Leon', fecha: '2026-06-01', estado: 'Asistio', entrada: '08:01', salida: '17:00', horasExtras: 0, horasCanjeables: 2 },
]);

const selectedRecord = ref(attendance.value[0]);

const dayDetail = reactive({
    estado: selectedRecord.value.estado,
    horasCanjeables: selectedRecord.value.horasCanjeables,
    horasExtras: selectedRecord.value.horasExtras,
    observaciones: 'Ingreso registrado dentro de tolerancia.',
});

const columns = [
    { key: 'trabajador', label: 'Trabajador' },
    { key: 'fecha', label: 'Fecha' },
    { key: 'estado', label: 'Estado' },
    { key: 'jornada', label: 'Jornada' },
    { key: 'extras', label: 'Extras' },
];

const counters = computed(() => ({
    asistio: attendance.value.filter((item) => item.estado === 'Asistio').length,
    pendiente: attendance.value.filter((item) => item.estado === 'Pendiente').length,
    falta: attendance.value.filter((item) => item.estado === 'Falta').length,
}));

const selectRecord = (record) => {
    selectedRecord.value = record;
    dayDetail.estado = record.estado;
    dayDetail.horasCanjeables = record.horasCanjeables;
    dayDetail.horasExtras = record.horasExtras;
    dayDetail.observaciones = record.estado === 'Falta' ? 'Sin marca de ingreso.' : 'Detalle editable del dia.';
};

const saveDetail = () => {
    selectedRecord.value.estado = dayDetail.estado;
    selectedRecord.value.horasCanjeables = Number(dayDetail.horasCanjeables);
    selectedRecord.value.horasExtras = Number(dayDetail.horasExtras);
};

const statusClass = (status) => ({
    Asistio: 'bg-green-100 text-green-700',
    Pendiente: 'bg-secondary/15 text-secondary',
    Falta: 'bg-danger/15 text-danger',
}[status]);
</script>

<template>
    <Head title="Asistencia" />

    <AuthenticatedLayout title="Asistencia">
        <section class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-500">Asistidos</p>
                        <CheckCircle2 class="h-5 w-5 text-green-600" />
                    </div>
                    <p class="mt-2 text-3xl font-black text-green-600">{{ counters.asistio }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-500">Pendientes</p>
                        <Clock class="h-5 w-5 text-secondary" />
                    </div>
                    <p class="mt-2 text-3xl font-black text-secondary">{{ counters.pendiente }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-gray-500">Faltas</p>
                        <TriangleAlert class="h-5 w-5 text-danger" />
                    </div>
                    <p class="mt-2 text-3xl font-black text-danger">{{ counters.falta }}</p>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <CalendarDays class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-gray-900">Registro diario</h1>
                            <p class="text-sm text-gray-500">Selecciona una fila para editar el detalle del dia.</p>
                        </div>
                    </div>

                    <DataTable :columns="columns">
                        <tr v-for="record in attendance" :key="record.id"
                            class="cursor-pointer text-sm transition hover:bg-primary/5"
                            :class="selectedRecord.id === record.id ? 'bg-primary/5' : ''"
                            @click="selectRecord(record)">
                            <td class="px-6 py-4 font-bold text-gray-800">{{ record.trabajador }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ record.fecha }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-bold" :class="statusClass(record.estado)">
                                    {{ record.estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ record.entrada || '--' }} / {{ record.salida || '--' }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800">{{ record.horasExtras }} h</td>
                        </tr>
                    </DataTable>
                </section>

                <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <h2 class="text-lg font-black text-gray-900">Detalle del Dia</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ selectedRecord.trabajador }}</p>

                    <form class="mt-5 space-y-4" @submit.prevent="saveDetail">
                        <label class="block text-sm font-semibold text-gray-700">
                            Estado
                            <select v-model="dayDetail.estado"
                                class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary">
                                <option>Asistio</option>
                                <option>Pendiente</option>
                                <option>Falta</option>
                                <option>Tardanza</option>
                                <option>Permiso</option>
                            </select>
                        </label>
                        <label class="block text-sm font-semibold text-gray-700">
                            Horas Canjeables
                            <input v-model.number="dayDetail.horasCanjeables" min="0" step="0.5" type="number"
                                class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                        </label>
                        <label class="block text-sm font-semibold text-gray-700">
                            Horas Extras
                            <input v-model.number="dayDetail.horasExtras" min="0" step="0.5" type="number"
                                class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                        </label>
                        <label class="block text-sm font-semibold text-gray-700">
                            Observaciones
                            <textarea v-model="dayDetail.observaciones" rows="5"
                                class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </label>

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white hover:bg-primary-dark">
                            <Save class="h-4 w-4" />
                            Guardar detalle
                        </button>
                    </form>
                </aside>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
