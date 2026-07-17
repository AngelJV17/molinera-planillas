<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { confirmAction, confirmCloseAttendance } from '@/Utils/alerts';

import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import ListSummary from '@/Components/Common/ListSummary.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';

import DataTable from '@/Components/Table/DataTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import TableActionButton from '@/Components/Table/TableActionButton.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';

import {
    CalendarCheck,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Database,
    FileDown,
    FileSpreadsheet,
    Plus,
    RefreshCcw,
    Upload,
    UserCheck,
} from 'lucide-vue-next';

const props = defineProps({
    attendances: {
        type: Object,
        required: true,
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    employees: {
        type: Array,
        default: () => [],
    },

    monthlyStatuses: {
        type: Array,
        default: () => [],
    },

    monthOptions: {
        type: Array,
        default: () => [],
    },

    yearOptions: {
        type: Array,
        default: () => [],
    },

    defaultMonth: {
        type: Number,
        required: true,
    },

    defaultYear: {
        type: Number,
        required: true,
    },

    allowedPeriods: {
        type: Array,
        default: () => [],
    },

    defaultPeriod: {
        type: String,
        required: true,
    },

    payrollGroupOptions: {
        type: Array,
        default: () => [],
    },
});

/**
 * Filtros principales del listado.
 */
const search = ref(props.filters.search ?? '');
const statusId = ref(props.filters.status_id ?? '');
const period = ref(props.filters.period ?? '');
const perPage = ref(props.filters.per_page ?? 10);
const page = usePage();
const entryMode = ref('manual');

let filterTimeout = null;
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permission) => permissions.value.includes(permission);
const canManageAttendance = computed(() => can('attendance.edit') || can('attendance.close') || can('attendance.reopen'));

/**
 * Formulario para crear una asistencia mensual.
 */
const form = useForm({
    employee_id: '',
    period: props.defaultPeriod,
    observations: '',
});

const importForm = useForm({
    period: props.defaultPeriod,
    payroll_group_id: props.payrollGroupOptions[0]?.id ?? '',
    file: null,
});

/**
 * Columnas visibles de la tabla.
 */
const baseColumns = [
    { key: 'employee', label: 'Trabajador' },
    { key: 'period', label: 'Periodo' },
    { key: 'summary', label: 'Resumen mensual' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const columns = computed(() => {
    return canManageAttendance.value
        ? baseColumns
        : baseColumns.filter((column) => column.key !== 'actions');
});

/**
 * Total de asistencias registradas.
 */
const totalAttendances = computed(() => {
    return props.attendances.total ?? props.attendances.data.length;
});

const selectedImportFileName = computed(() => {
    return importForm.file?.name ?? 'Sin archivo seleccionado';
});

const templateDownloadUrl = computed(() => {
    if (!importForm.period || !importForm.payroll_group_id) {
        return '#';
    }

    return route('attendance.import-template', {
        period: importForm.period,
        payroll_group_id: importForm.payroll_group_id,
    });
});

/**
 * Aplica filtros al listado.
 */
const applyFilters = () => {
    router.get(
        route('attendance.index'),
        {
            search: search.value || undefined,
            status_id: statusId.value || undefined,
            period: period.value || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

/**
 * Observa cambios en filtros con un pequeño retardo
 * para evitar demasiadas peticiones al escribir.
 */
watch([search, statusId, period, perPage], () => {
    clearTimeout(filterTimeout);

    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

/**
 * Registra la cabecera mensual de asistencia.
 *
 * El formulario usa un campo visual llamado "period",
 * pero el backend recibe month y year separados.
 */
const submit = () => {
    const [selectedYear, selectedMonth] = String(form.period).split('-');

    form
        .transform((data) => ({
            employee_id: data.employee_id,
            month: Number(selectedMonth),
            year: Number(selectedYear),
            observations: data.observations,
        }))
        .post(route('attendance.store'), {
            preserveScroll: true,

            onSuccess: () => {
                form.reset('employee_id', 'observations');
            },
        });
};

const importBulkExcel = () => {
    if (!importForm.file || importForm.processing) {
        return;
    }

    importForm.post(route('attendance.import-excel'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => importForm.reset('file'),
    });
};

/**
 * Cierra una asistencia mensual.
 */
const closeAttendance = async (attendance) => {
    const confirmed = await confirmCloseAttendance({
        employeeName: attendance.employee.name,
        period: attendance.period,
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('attendance.close', attendance.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const reopenAttendance = async (attendance) => {
    const confirmed = await confirmAction({
        title: 'Reabrir asistencia',
        text: `Se habilitara la edicion de la asistencia de ${attendance.employee.name} correspondiente a ${attendance.period}.`,
        icon: 'warning',
        confirmButtonText: 'Reabrir asistencia',
    });

    if (!confirmed) {
        return;
    }

    router.patch(
        route('attendance.reopen', attendance.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

/**
 * Obtiene clases visuales para el estado mensual.
 */
const statusClasses = (status) => {
    if (status?.code === 'CLOSED') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    return 'border-amber-200 bg-amber-50 text-amber-700';
};

/**
 * Obtiene texto del estado mensual.
 */
const statusLabel = (status) => {
    return status?.name ?? 'Sin estado';
};
</script>

<template>

    <Head title="Asistencia mensual" />

    <AuthenticatedLayout title="Asistencia mensual">
        <section class="space-y-6">
            <PageHeader title="Asistencia mensual"
                description="Registra y controla la asistencia mensual de cada trabajador mediante un calendario diario.">
                <template #icon>
                    <CalendarCheck class="h-7 w-7" />
                </template>
            </PageHeader>

            <!-- Formulario de creación de asistencia mensual -->
            <div v-if="can('attendance.create') || can('attendance.edit')" class="grid max-w-xl grid-cols-2 rounded-xl border border-slate-200 bg-slate-100 p-1">
                <button v-if="can('attendance.create')" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-black transition"
                    :class="entryMode === 'manual' ? 'bg-white text-primary shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    @click="entryMode = 'manual'">
                    <Plus class="h-4 w-4" />
                    Carga manual
                </button>

                <button v-if="can('attendance.edit')" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-black transition"
                    :class="entryMode === 'bulk' ? 'bg-white text-primary shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    @click="entryMode = 'bulk'">
                    <FileSpreadsheet class="h-4 w-4" />
                    Carga masiva
                </button>
            </div>

            <SectionCard v-if="entryMode === 'manual' && can('attendance.create')" title="Nuevo control mensual"
                description="Selecciona un trabajador, mes y año para generar automáticamente su calendario de asistencia.">
                <form class="grid gap-4 lg:grid-cols-[1fr_240px_auto]" @submit.prevent="submit">
                    <!-- Trabajador -->
                    <div>
                        <InputLabel for="employee_id" value="Trabajador" required />

                        <select id="employee_id" v-model="form.employee_id"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            required>
                            <option value="">
                                Selecciona un trabajador
                            </option>

                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.name }} - {{ employee.code }}
                            </option>
                        </select>

                        <InputError class="mt-2" :message="form.errors.employee_id" />
                    </div>

                    <!-- Periodo permitido -->
                    <div>
                        <InputLabel for="period" value="Periodo permitido" required />

                        <select id="period" v-model="form.period"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            required>
                            <option v-for="option in allowedPeriods" :key="option.value" :value="option.value">
                                {{ option.label }}
                                {{ option.is_current ? '(Actual)' : '(Mes anterior)' }}
                            </option>
                        </select>

                        <InputError class="mt-2"
                            :message="form.errors.period || form.errors.month || form.errors.year" />
                    </div>

                    <!-- Botón -->
                    <div class="flex items-end">
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing">
                            <Plus class="h-4 w-4" />
                            Crear
                        </button>
                    </div>

                    <!-- Observaciones -->
                    <div class="lg:col-span-3">
                        <InputLabel for="observations" value="Observaciones generales" optional />

                        <textarea id="observations" v-model="form.observations" rows="2"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            placeholder="Observación opcional para esta asistencia mensual." />

                        <InputError class="mt-2" :message="form.errors.observations" />
                    </div>
                </form>
            </SectionCard>

            <SectionCard v-if="entryMode === 'bulk' && can('attendance.edit')" title="Importar asistencia masiva"
                description="Carga la matriz mensual por grupo de planilla. Cada trabajador va en una fila y los dias del mes se llenan con A, F, D, C o S.">
                <form class="space-y-5" @submit.prevent="importBulkExcel">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel for="bulk_period" value="Periodo" required />

                            <select id="bulk_period" v-model="importForm.period"
                                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                required>
                                <option v-for="option in allowedPeriods" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <InputLabel for="bulk_payroll_group_id" value="Grupo de planilla" required />

                            <select id="bulk_payroll_group_id" v-model="importForm.payroll_group_id"
                                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                required>
                                <option value="">Selecciona un grupo</option>

                                <option v-for="group in payrollGroupOptions" :key="group.id" :value="group.id">
                                    {{ group.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div>
                            <InputLabel for="bulk_attendance_file" value="Archivo Excel" required />

                            <div class="mt-2 grid gap-3 xl:grid-cols-[minmax(0,1fr)_150px]">
                                <div class="flex h-12 min-w-0 overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
                                    <input id="bulk_attendance_file" type="file" accept=".xlsx,.xls,.csv"
                                        class="sr-only"
                                        @change="importForm.file = $event.target.files[0]" />

                                    <label for="bulk_attendance_file"
                                        class="inline-flex h-full shrink-0 cursor-pointer items-center justify-center bg-primary px-4 text-sm font-bold text-white transition hover:bg-primary-dark">
                                        Seleccionar archivo
                                    </label>

                                    <span class="flex min-w-0 flex-1 items-center truncate px-4 text-sm font-semibold text-slate-700">
                                        {{ selectedImportFileName }}
                                    </span>
                                </div>

                                <button type="submit"
                                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-bold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                                    :disabled="!importForm.file || !importForm.payroll_group_id || importForm.processing">
                                    <Upload class="h-4 w-4" />
                                    {{ importForm.processing ? 'Importando...' : 'Importar' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="-mt-2 text-xs font-semibold text-slate-500">
                        Usa la hoja Asistencia para la matriz mensual; Horas extras y Canjes solo para casos especiales.
                    </p>
                </form>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-sm font-semibold text-emerald-800">
                        Usa la plantilla oficial con trabajadores por fila, dias por columnas e instrucciones incluidas.
                    </p>

                    <a :href="templateDownloadUrl"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition hover:bg-emerald-100">
                        <FileDown class="h-4 w-4" />
                        Descargar formato
                    </a>
                </div>
            </SectionCard>

            <!-- Filtros -->
            <FilterCard>
                <template #filters>
                    <SearchInput v-model="search" placeholder="Buscar por trabajador, código o documento..." />

                    <select v-model="period"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">
                            Todos los periodos permitidos
                        </option>

                        <option v-for="option in allowedPeriods" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <select v-model="statusId"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">
                            Todos los estados
                        </option>

                        <option v-for="status in monthlyStatuses" :key="status.id" :value="status.id">
                            {{ status.name }}
                        </option>
                    </select>

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <!-- Resumen -->
            <ListSummary title="Controles mensuales registrados"
                description="Listado de asistencias mensuales generadas por trabajador." label="Total controles"
                :total="totalAttendances" :icon="Database" />

            <!-- Tabla -->
            <DataTable :columns="columns">
                <tr v-for="attendance in attendances.data" :key="attendance.id"
                    class="text-sm transition hover:bg-primary/5">
                    <!-- Trabajador -->
                    <td class="px-6 py-4">
                        <TableEntityCell :icon="UserCheck" :title="attendance.employee.name"
                            :subtitle="`Documento: ${attendance.employee.document}`"
                            :meta="`Código: ${attendance.employee.code}`" />
                    </td>

                    <!-- Periodo -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <CalendarDays class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="font-black text-gray-800">
                                    {{ attendance.period }}
                                </p>

                                <p class="text-xs font-semibold text-gray-500">
                                    Mes {{ attendance.month }} / Año {{ attendance.year }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <!-- Resumen mensual -->
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700">
                                Asistió: {{ attendance.worked_days }}
                            </span>

                            <span class="rounded-full bg-red-50 px-3 py-1 text-[11px] font-bold text-red-700">
                                Faltas: {{ attendance.absence_days }}
                            </span>

                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-700">
                                Canjes: {{ attendance.exchange_days }}
                            </span>

                            <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold text-amber-700">
                                Pendientes desc.: {{ attendance.uncompensated_absence_days }}
                            </span>

                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-700">
                                H. extras: {{ attendance.overtime_hours }}
                            </span>

                            <span class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold text-primary">
                                H. extra pago: {{ attendance.payable_overtime_hours }}
                            </span>
                        </div>
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4">
                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-black"
                            :class="statusClasses(attendance.status)">
                            {{ statusLabel(attendance.status) }}
                        </span>
                    </td>

                    <!-- Acciones -->
                    <td v-if="canManageAttendance" class="px-6 py-4">
                        <TableActions>
                            <TableActionButton v-if="can('attendance.edit')" :href="route('attendance.edit', attendance.id)" :icon="CalendarDays"
                                title="Abrir calendario" />

                            <TableActionButton v-if="attendance.is_editable && can('attendance.close')" :icon="CheckCircle2"
                                title="Cerrar asistencia" variant="success" @click="closeAttendance(attendance)" />

                            <TableActionButton v-if="attendance.can_reopen && can('attendance.reopen')" :icon="RefreshCcw"
                                title="Reabrir asistencia" variant="warning" @click="reopenAttendance(attendance)" />
                        </TableActions>
                    </td>
                </tr>

                <template v-if="attendances.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState title="No se encontraron asistencias mensuales"
                            description="Registra un nuevo control mensual o modifica los filtros de búsqueda.">
                            <template #icon>
                                <Clock3 class="h-10 w-10" />
                            </template>
                        </EmptyState>
                    </td>
                </template>
            </DataTable>

            <!-- Paginación -->
            <div v-if="attendances.links?.length > 3"
                class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="attendances.links" />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
