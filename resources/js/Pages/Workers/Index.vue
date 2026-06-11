<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import {
    BriefcaseBusiness,
    Edit,
    Plus,
    Save,
    UserRound,
    Users,
    SlidersHorizontal,
    X,
    Power,
    ListChecks,
    Database,
} from 'lucide-vue-next';

const props = defineProps({
    /* workers: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    }, */
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const perPage = ref(props.filters.per_page ?? 10);

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

const showWorkerModal = ref(false);

const workerForm = reactive({
    nombres: '',
    documento: '',
    cargo: 'Operario de molino',
    area: 'Produccion',
    sueldoBasico: 1130,
    asignacionFamiliar: false,
    estado: 'Activo',
});

const workers = ref([
    {
        id: 1,
        nombres: 'Juan Perez Huaman',
        documento: '45871236',
        cargo: 'Operario de molino',
        area: 'Produccion',
        sueldoBasico: 1450,
        asignacionFamiliar: true,
        estado: 'Activo',
    },
    {
        id: 2,
        nombres: 'Maria Quispe Rojas',
        documento: '70124589',
        cargo: 'Auxiliar administrativo',
        area: 'Administracion',
        sueldoBasico: 1800,
        asignacionFamiliar: false,
        estado: 'Activo',
    },
    {
        id: 3,
        nombres: 'Carlos Mendoza Salas',
        documento: '42588963',
        cargo: 'Supervisor de planta',
        area: 'Produccion',
        sueldoBasico: 2450,
        asignacionFamiliar: true,
        estado: 'Activo',
    },
]);

const columns = [
    { key: 'nombres', label: 'Trabajador' },
    { key: 'documento', label: 'Documento' },
    { key: 'cargo', label: 'Cargo' },
    { key: 'area', label: 'Area' },
    { key: 'sueldo', label: 'Sueldo basico' },
    { key: 'estado', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const openModal = () => {
    showWorkerModal.value = true;
};

const saveWorker = () => {
    workers.value.unshift({
        id: Date.now(),
        ...workerForm,
        sueldoBasico: Number(workerForm.sueldoBasico),
    });

    workerForm.nombres = '';
    workerForm.documento = '';
    workerForm.cargo = 'Operario de molino';
    workerForm.area = 'Produccion';
    workerForm.sueldoBasico = 1130;
    workerForm.asignacionFamiliar = false;
    workerForm.estado = 'Activo';
    showWorkerModal.value = false;
};

const money = (amount) => `S/ ${Number(amount).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`;
</script>

<template>

    <Head title="Trabajadores" />

    <AuthenticatedLayout title="Trabajadores">
        <section class="space-y-6">
            <div class="overflow-hidden rounded-3xl border border-primary/20 bg-white shadow-lg">
                <div class="h-1.5 bg-primary"></div>
                <div class="flex flex-col justify-between gap-5 p-6 lg:flex-row lg:items-center">
                    <div class="flex gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                            <Users class="h-7 w-7" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-gray-900">Gestion de trabajadores</h1>
                            <p class="mt-1 max-w-3xl text-sm text-gray-600">
                                Registra datos laborales, remuneracion base y beneficios del personal de MOLICENTE.
                            </p>
                        </div>
                    </div>

                    <button type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-primary-dark"
                        @click="openModal">
                        <Plus class="h-4 w-4" />
                        Nuevo trabajador
                    </button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <p class="text-sm font-semibold text-gray-500">Personal activo</p>
                    <p class="mt-2 text-3xl font-black text-primary">{{ workers.length }}</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <p class="text-sm font-semibold text-gray-500">Con asignacion familiar</p>
                    <p class="mt-2 text-3xl font-black text-secondary">
                        {{workers.filter((worker) => worker.asignacionFamiliar).length}}
                    </p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md">
                    <p class="text-sm font-semibold text-gray-500">Sueldo promedio</p>
                    <p class="mt-2 text-3xl font-black text-gray-800">
                        {{money(workers.reduce((sum, worker) => sum + worker.sueldoBasico, 0) / workers.length)}}
                    </p>
                </article>
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

                        <!-- <p class="text-2xl font-black leading-none text-primary">
                            {{ catalogs.total ?? catalogs.data.length }}
                        </p> -->
                    </div>
                </div>
            </div>

            <DataTable :columns="columns">
                <tr v-for="worker in workers" :key="worker.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <UserRound class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-bold text-gray-800">{{ worker.nombres }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ worker.asignacionFamiliar ? 'Con asignacion familiar' : 'Sin asignacion familiar'
                                    }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-xs font-bold text-gray-700">{{ worker.documento }}</td>
                    <td class="px-6 py-4 text-gray-700">{{ worker.cargo }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ worker.area }}</td>
                    <td class="px-6 py-4 font-bold text-gray-800">{{ money(worker.sueldoBasico) }}</td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-primary/15 px-3 py-1 text-xs font-bold text-primary">{{
                            worker.estado }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-end">
                            <button type="button"
                                class="rounded-xl border border-slate-300 bg-white p-2 text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/10 hover:text-primary">
                                <Edit class="h-4 w-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </DataTable>
        </section>

        <Modal :show="showWorkerModal" max-width="2xl" @close="showWorkerModal = false">
            <form class="p-6" @submit.prevent="saveWorker">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="text-xl font-black text-gray-900">Registrar trabajador</h2>
                        <p class="mt-1 text-sm text-gray-500">Completa la informacion laboral para la planilla.</p>
                    </div>
                    <button type="button" class="rounded-xl p-2 text-gray-500 hover:bg-slate-100"
                        @click="showWorkerModal = false">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <label class="text-sm font-semibold text-gray-700">
                        Nombres y apellidos
                        <input v-model="workerForm.nombres" required type="text"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        DNI
                        <input v-model="workerForm.documento" required type="text"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Cargo
                        <input v-model="workerForm.cargo" type="text"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Area
                        <select v-model="workerForm.area"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary">
                            <option>Produccion</option>
                            <option>Administracion</option>
                            <option>Almacen</option>
                            <option>Ventas</option>
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Sueldo Basico
                        <input v-model.number="workerForm.sueldoBasico" min="0" step="0.01" required type="number"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    </label>
                    <label class="text-sm font-semibold text-gray-700">
                        Estado
                        <select v-model="workerForm.estado"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary">
                            <option>Activo</option>
                            <option>Inactivo</option>
                            <option>Suspendido</option>
                        </select>
                    </label>
                    <label
                        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-gray-700 md:col-span-2">
                        <input v-model="workerForm.asignacionFamiliar" type="checkbox"
                            class="rounded border-gray-300 text-primary focus:ring-primary" />
                        Asignacion Familiar
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-slate-50"
                        @click="showWorkerModal = false">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-dark">
                        <Save class="h-4 w-4" />
                        Guardar
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
