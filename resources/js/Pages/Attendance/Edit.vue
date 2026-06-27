<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';

import {
    AlertCircle,
    ArrowLeft,
    Ban,
    CalendarCheck,
    CalendarDays,
    CheckCircle2,
    Info,
    RefreshCcw,
    Save,
    UserCheck,
} from 'lucide-vue-next';

const props = defineProps({
    attendance: {
        type: Object,
        required: true,
    },

    dayStatuses: {
        type: Array,
        default: () => [],
    },
});

/**
 * Día seleccionado para editar.
 */
const selectedDay = ref(null);

/**
 * Formulario para actualizar un día del calendario.
 */
const dayForm = useForm({
    status_id: '',
    overtime_hours: 0,
    observation: '',
});

/**
 * Cabecera del calendario.
 * Se usa lunes como primer día de la semana.
 */
const weekDays = [
    'Lun',
    'Mar',
    'Mié',
    'Jue',
    'Vie',
    'Sáb',
    'Dom',
];

/**
 * Fecha actual local.
 * Sirve para bloquear días futuros en la interfaz.
 */
const today = new Date();
today.setHours(0, 0, 0, 0);

/**
 * Indica si la asistencia mensual está editable.
 */
const canEdit = computed(() => {
    return props.attendance.status?.code !== 'CLOSED';
});

/**
 * Obtiene el primer día real del mes.
 */
const firstDate = computed(() => {
    const firstDay = props.attendance.days[0]?.attendance_date;

    if (!firstDay) {
        return null;
    }

    return new Date(`${firstDay}T00:00:00`);
});

/**
 * Calcula los espacios vacíos antes del día 1.
 *
 * JavaScript:
 * - Domingo = 0
 * - Lunes = 1
 *
 * Calendario laboral:
 * - Lunes = 0
 * - Domingo = 6
 */
const blankDaysBeforeMonth = computed(() => {
    if (!firstDate.value) {
        return [];
    }

    const jsDay = firstDate.value.getDay();
    const mondayBasedDay = (jsDay + 6) % 7;

    return Array.from({ length: mondayBasedDay }, (_, index) => ({
        id: `blank-${index}`,
        blank: true,
    }));
});

/**
 * Celdas completas del calendario.
 * Incluye espacios vacíos y días reales.
 */
const calendarCells = computed(() => {
    return [
        ...blankDaysBeforeMonth.value,
        ...props.attendance.days,
    ];
});

/**
 * Totales principales del mes.
 */
const totals = computed(() => {
    const workedDays = props.attendance.days.filter((day) => day.status?.code === 'PRESENT').length;
    const absenceDays = props.attendance.days.filter((day) => day.status?.code === 'ABSENT').length;
    const exchangeDays = props.attendance.days.filter((day) => day.status?.code === 'EXCHANGE_WORKED').length;
    const restDays = props.attendance.days.filter((day) => day.status?.code === 'REST').length;
    const unmarkedDays = props.attendance.days.filter((day) => day.status?.code === 'UNMARKED').length;

    const overtimeHours = props.attendance.days.reduce((total, day) => {
        return total + Number(day.overtime_hours ?? 0);
    }, 0);

    return {
        workedDays,
        absenceDays,
        exchangeDays,
        restDays,
        unmarkedDays,
        overtimeHours: overtimeHours.toFixed(2),
    };
});

/**
 * Convierte una fecha YYYY-MM-DD a fecha local.
 */
const parseLocalDate = (dateString) => {
    return new Date(`${dateString}T00:00:00`);
};

/**
 * Indica si el día pertenece al futuro.
 */
const isFutureDay = (day) => {
    if (!day || day.blank || !day.attendance_date) {
        return false;
    }

    const dayDate = parseLocalDate(day.attendance_date);
    dayDate.setHours(0, 0, 0, 0);

    return dayDate > today;
};

/**
 * Indica si un día puede editarse.
 *
 * Reglas:
 * - No debe ser celda vacía.
 * - La asistencia mensual no debe estar cerrada.
 * - El día no debe ser futuro.
 */
const canEditDay = (day) => {
    return !day.blank && canEdit.value && !isFutureDay(day);
};

/**
 * Abre el formulario lateral con los datos del día seleccionado.
 */
const openDay = (day) => {
    if (!canEditDay(day)) {
        return;
    }

    selectedDay.value = day;

    dayForm.status_id = day.status?.id ?? '';
    dayForm.overtime_hours = day.overtime_hours ?? 0;
    dayForm.observation = day.observation ?? '';
    dayForm.clearErrors();
};

/**
 * Guarda los cambios del día seleccionado.
 */
const updateDay = () => {
    if (!selectedDay.value || !canEdit.value) {
        return;
    }

    dayForm.patch(route('attendance.days.update', selectedDay.value.id), {
        preserveScroll: true,

        onSuccess: () => {
            selectedDay.value = null;
        },
    });
};

/**
 * Cierra el panel lateral de edición.
 */
const closeDayPanel = () => {
    selectedDay.value = null;
    dayForm.clearErrors();
};

/**
 * Cierra la asistencia mensual.
 */
const closeAttendance = () => {
    const confirmed = window.confirm(
        `¿Deseas cerrar la asistencia de ${props.attendance.employee.name} correspondiente a ${props.attendance.period}?`,
    );

    if (!confirmed) {
        return;
    }

    router.patch(
        route('attendance.close', props.attendance.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

/**
 * Clases visuales para cada celda del calendario.
 */
const dayCellClasses = (day) => {
    if (day.blank) {
        return 'cursor-default bg-slate-50 text-slate-300';
    }

    if (isFutureDay(day)) {
        return 'cursor-not-allowed bg-slate-50 text-slate-400';
    }

    const classes = {
        UNMARKED: 'bg-white text-slate-700 hover:bg-slate-50',
        PRESENT: 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100',
        ABSENT: 'bg-red-50 text-red-800 hover:bg-red-100',
        EXCHANGE_WORKED: 'bg-blue-50 text-blue-800 hover:bg-blue-100',
        REST: 'bg-amber-50 text-amber-800 hover:bg-amber-100',
    };

    return classes[day.status?.code] ?? classes.UNMARKED;
};

/**
 * Clases visuales para el badge del estado diario.
 */
const dayBadgeClasses = (status) => {
    const classes = {
        UNMARKED: 'bg-slate-100 text-slate-600 ring-slate-200',
        PRESENT: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        ABSENT: 'bg-red-100 text-red-700 ring-red-200',
        EXCHANGE_WORKED: 'bg-blue-100 text-blue-700 ring-blue-200',
        REST: 'bg-amber-100 text-amber-700 ring-amber-200',
    };

    return classes[status?.code] ?? classes.UNMARKED;
};

/**
 * Texto corto del estado diario.
 */
const shortStatusLabel = (status) => {
    const labels = {
        UNMARKED: 'Sin marcar',
        PRESENT: 'Asistió',
        ABSENT: 'Faltó',
        EXCHANGE_WORKED: 'Canje',
        REST: 'Descanso',
    };

    return labels[status?.code] ?? status?.name ?? 'Sin marcar';
};

/**
 * Clases visuales para el estado mensual.
 */
const monthlyStatusClasses = computed(() => {
    if (props.attendance.status?.code === 'CLOSED') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    return 'border-amber-200 bg-amber-50 text-amber-700';
});
</script>

<template>

    <Head :title="`Calendario - ${attendance.period}`" />

    <AuthenticatedLayout title="Calendario de asistencia">
        <section class="space-y-6">
            <PageHeader :title="`Calendario de asistencia - ${attendance.period}`"
                description="Registra la asistencia diaria del trabajador de forma ordenada.">
                <template #icon>
                    <CalendarCheck class="h-7 w-7" />
                </template>

                <template #actions>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="route('attendance.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50">
                            <ArrowLeft class="h-4 w-4" />
                            Volver
                        </Link>

                        <button v-if="canEdit" type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white shadow transition hover:bg-primary-dark"
                            @click="closeAttendance">
                            <CheckCircle2 class="h-4 w-4" />
                            Cerrar asistencia
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Contexto principal, contadores y reglas -->
            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                <!-- Control mensual con contadores integrados -->
                <SectionCard title="Control mensual"
                    description="Información del trabajador, periodo, estado actual y resumen de asistencia.">
                    <div class="grid gap-6 xl:grid-cols-[1fr_1.15fr] xl:items-start">
                        <!-- Datos principales del trabajador -->
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <UserCheck class="h-7 w-7" />
                            </div>

                            <div class="min-w-0">
                                <h2 class="text-xl font-black leading-tight text-gray-900">
                                    {{ attendance.employee.name }}
                                </h2>

                                <p class="mt-1 text-sm font-semibold text-gray-500">
                                    Documento: {{ attendance.employee.document }} · Código: {{ attendance.employee.code
                                    }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-black"
                                        :class="monthlyStatusClasses">
                                        {{ attendance.status.name }}
                                    </span>

                                    <span
                                        class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-black text-primary">
                                        {{ attendance.period }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Contadores integrados -->
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-wide text-primary">
                                    Pendientes
                                </p>

                                <p class="mt-1 text-2xl font-black text-primary">
                                    {{ totals.unmarkedDays }}
                                </p>

                                <p class="text-xs font-semibold text-gray-500">
                                    días sin marcar
                                </p>
                            </div>

                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-wide text-emerald-700">
                                    Asistió
                                </p>

                                <p class="mt-1 text-2xl font-black text-emerald-700">
                                    {{ totals.workedDays }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-wide text-red-700">
                                    Faltó
                                </p>

                                <p class="mt-1 text-2xl font-black text-red-700">
                                    {{ totals.absenceDays }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-wide text-blue-700">
                                    Canjes
                                </p>

                                <p class="mt-1 text-2xl font-black text-blue-700">
                                    {{ totals.exchangeDays }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-wide text-amber-700">
                                    Descansos
                                </p>

                                <p class="mt-1 text-2xl font-black text-amber-700">
                                    {{ totals.restDays }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-black uppercase tracking-wide text-slate-600">
                                    H. extras
                                </p>

                                <p class="mt-1 text-2xl font-black text-slate-700">
                                    {{ totals.overtimeHours }}
                                </p>
                            </div>
                        </div>
                    </div>
                </SectionCard>

                <!-- Reglas rápidas compactas -->
                <SectionCard title="Reglas rápidas" description="Antes de marcar asistencia, considera estas reglas.">
                    <div class="space-y-3">
                        <div class="flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <Ban class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" />

                            <p class="text-sm font-semibold leading-relaxed text-slate-600">
                                No se pueden marcar días futuros.
                            </p>
                        </div>

                        <div class="flex gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-3">
                            <RefreshCcw class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                            <p class="text-sm font-semibold leading-relaxed text-blue-800">
                                Para canje: marca una falta y otro día como canje. Luego se vinculan.
                            </p>
                        </div>

                        <div class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-3">
                            <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                            <p class="text-sm font-semibold leading-relaxed text-amber-800">
                                Para cerrar, todos los días permitidos deben estar registrados.
                            </p>
                        </div>
                    </div>
                </SectionCard>
            </div>

            <!-- Calendario y formulario lateral -->
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
                <!-- Calendario -->
                <SectionCard title="Calendario mensual"
                    description="Haz clic en un día disponible para registrar o corregir su asistencia.">
                    <!-- Leyenda compacta -->
                    <div class="mb-4 flex flex-wrap gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                            Sin marcar
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black text-emerald-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Asistió
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-black text-red-700">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            Faltó
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-black text-blue-700">
                            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                            Canje
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-black text-amber-700">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Descanso
                        </span>
                    </div>

                    <!-- Calendario compacto sin scroll horizontal -->
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <!-- Cabecera de días -->
                        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-100">
                            <div v-for="weekDay in weekDays" :key="weekDay"
                                class="px-2 py-2 text-center text-[11px] font-black uppercase tracking-wide text-slate-500">
                                {{ weekDay }}
                            </div>
                        </div>

                        <!-- Celdas del calendario -->
                        <div class="grid grid-cols-7">
                            <button v-for="cell in calendarCells" :key="cell.id" type="button"
                                class="min-h-[92px] border-b border-r border-slate-200 p-2 text-left transition" :class="[
                                    dayCellClasses(cell),
                                    selectedDay?.id === cell.id
                                        ? 'relative z-10 ring-2 ring-primary ring-inset'
                                        : '',
                                    !canEditDay(cell) && !cell.blank
                                        ? 'opacity-80'
                                        : '',
                                ]" :disabled="!canEditDay(cell)" @click="openDay(cell)">
                                <template v-if="!cell.blank">
                                    <!-- Número del día -->
                                    <div class="flex items-start justify-between gap-1">
                                        <div>
                                            <p class="text-xl font-black leading-none">
                                                {{ Number(cell.day_number) }}
                                            </p>

                                            <p class="mt-1 text-[10px] font-black uppercase leading-none opacity-70">
                                                {{ cell.weekday }}
                                            </p>
                                        </div>

                                        <Ban v-if="isFutureDay(cell)" class="h-3.5 w-3.5 text-slate-400" />
                                    </div>

                                    <!-- Estado separado y compacto -->
                                    <div class="mt-3">
                                        <span v-if="!isFutureDay(cell)"
                                            class="inline-flex max-w-full rounded-full px-2 py-0.5 text-[10px] font-black ring-1"
                                            :class="dayBadgeClasses(cell.status)">
                                            {{ shortStatusLabel(cell.status) }}
                                        </span>

                                        <span v-else
                                            class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-black text-slate-500">
                                            Futuro
                                        </span>
                                    </div>

                                    <!-- Datos adicionales compactos -->
                                    <div class="mt-2 space-y-1">
                                        <p v-if="Number(cell.overtime_hours) > 0"
                                            class="inline-flex rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-black shadow-sm">
                                            HE: {{ cell.overtime_hours }}
                                        </p>

                                        <p v-if="cell.observation"
                                            class="line-clamp-1 rounded-lg bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold shadow-sm">
                                            {{ cell.observation }}
                                        </p>
                                    </div>
                                </template>
                            </button>
                        </div>
                    </div>
                </SectionCard>

                <!-- Panel lateral de edición -->
                <SectionCard title="Editar día" description="Selecciona un día disponible para registrar su estado."
                    class="xl:sticky xl:top-6 xl:self-start">
                    <div v-if="!selectedDay"
                        class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                        <CalendarDays class="mx-auto h-10 w-10 text-primary" />

                        <h3 class="mt-4 text-lg font-black text-gray-900">
                            Selecciona un día
                        </h3>

                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                            Elige un día disponible del calendario. Los días futuros
                            y asistencias cerradas no se pueden editar.
                        </p>
                    </div>

                    <form v-else class="space-y-4" @submit.prevent="updateDay">
                        <!-- Día seleccionado -->
                        <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
                            <p class="text-xs font-black uppercase tracking-wide text-primary">
                                Día seleccionado
                            </p>

                            <p class="mt-1 text-lg font-black text-gray-900">
                                {{ selectedDay.attendance_date }}
                            </p>

                            <p class="text-sm font-semibold text-gray-500">
                                Estado actual: {{ selectedDay.status.name }}
                            </p>
                        </div>

                        <!-- Estado del día -->
                        <div>
                            <InputLabel for="status_id" value="Estado del día" />

                            <select id="status_id" v-model="dayForm.status_id"
                                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                :disabled="!canEdit" required>
                                <option value="">
                                    Selecciona un estado
                                </option>

                                <option v-for="status in dayStatuses" :key="status.id" :value="status.id">
                                    {{ status.name }}
                                </option>
                            </select>

                            <InputError class="mt-2" :message="dayForm.errors.status_id" />
                        </div>

                        <!-- Horas extras -->
                        <div>
                            <InputLabel for="overtime_hours" value="Horas extras" />

                            <input id="overtime_hours" v-model="dayForm.overtime_hours" type="number" min="0" max="24"
                                step="0.5"
                                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                :disabled="!canEdit" placeholder="0.00" />

                            <InputError class="mt-2" :message="dayForm.errors.overtime_hours" />

                            <p class="mt-2 text-xs font-semibold text-slate-500">
                                Luego este valor se calculará automáticamente con hora de ingreso y salida.
                            </p>
                        </div>

                        <!-- Observación -->
                        <div>
                            <InputLabel for="observation" value="Observación" />

                            <textarea id="observation" v-model="dayForm.observation" rows="4"
                                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                :disabled="!canEdit" placeholder="Observación opcional del día." />

                            <InputError class="mt-2" :message="dayForm.errors.observation" />
                        </div>

                        <!-- Ayuda contextual -->
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                            <div class="flex items-start gap-3">
                                <Info class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                                <p class="text-xs font-semibold leading-relaxed text-blue-800">
                                    Si este día compensará una falta, márcalo como “Trabajó como canje”.
                                    Después vincularemos este día con la fecha exacta de la falta.
                                </p>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button"
                                class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition hover:bg-slate-50"
                                @click="closeDayPanel">
                                Cancelar
                            </button>

                            <button v-if="canEdit" type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                                :disabled="dayForm.processing">
                                <Save class="h-4 w-4" />

                                {{ dayForm.processing ? 'Guardando...' : 'Guardar día' }}
                            </button>
                        </div>
                    </form>
                </SectionCard>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
