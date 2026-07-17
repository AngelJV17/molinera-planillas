<script setup>
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { confirmAction, confirmCloseAttendance } from "@/Utils/alerts";
import { formatDate } from "@/Utils/dates";

import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PageHeader from "@/Components/Common/PageHeader.vue";
import SectionCard from "@/Components/Common/SectionCard.vue";

import {
  AlertCircle,
  ArrowLeft,
  Ban,
  CalendarCheck,
  CalendarDays,
  CheckCircle2,
  Clock3,
  Info,
  RefreshCcw,
  Save,
  UserCheck,
} from "lucide-vue-next";

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

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permission) => permissions.value.includes(permission);

/**
 * Día seleccionado para editar.
 */
const selectedDay = ref(null);

const bulkMode = ref(false);
const selectedDayIds = ref([]);

const bulkForm = useForm({
  day_ids: [],
  status_code: "",
});

/**
 * Formulario para actualizar un día del calendario.
 *
 * Las horas extras ya no se escriben manualmente.
 * Se calculan en backend usando:
 * - turno del trabajador
 * - hora de ingreso
 * - hora de salida
 */
const dayForm = useForm({
  status_id: "",
  entry_time: "",
  exit_time: "",
  absence_attendance_day_id: "",
  observation: "",
});

/**
 * Cabecera del calendario.
 */
const weekDays = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];

/**
 * Fecha actual local.
 * Sirve para bloquear días futuros en frontend.
 * El backend también debe validar esto.
 */
const today = new Date();
today.setHours(0, 0, 0, 0);

/**
 * Indica si la asistencia mensual está editable.
 */
const canEdit = computed(() => {
  return props.attendance.status?.code !== "CLOSED";
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
 */
const calendarCells = computed(() => {
  return [...blankDaysBeforeMonth.value, ...props.attendance.days];
});

/**
 * Totales principales del mes.
 */
const totals = computed(() => {
  const workedDays = props.attendance.days.filter((day) => day.status?.code === "PRESENT")
    .length;
  const absenceDays = props.attendance.days.filter((day) => day.status?.code === "ABSENT")
    .length;
  const exchangeDays = props.attendance.days.filter(
    (day) => day.status?.code === "EXCHANGE_WORKED"
  ).length;
  const restDays = props.attendance.days.filter((day) => day.status?.code === "REST")
    .length;
  const unmarkedDays = props.attendance.days.filter(
    (day) => day.status?.code === "UNMARKED"
  ).length;

  const workedHours = props.attendance.days.reduce((total, day) => {
    return total + Number(day.worked_hours ?? 0);
  }, 0);

  const overtimeHours = props.attendance.days.reduce((total, day) => {
    return total + Number(day.overtime_hours ?? 0);
  }, 0);

  const payableOvertimeHours = props.attendance.days.reduce((total, day) => {
    return total + Number(day.payable_overtime_hours ?? 0);
  }, 0);

  return {
    workedDays,
    absenceDays,
    exchangeDays,
    restDays,
    unmarkedDays,
    workedHours: workedHours.toFixed(2),
    overtimeHours: overtimeHours.toFixed(2),
    payableOvertimeHours: payableOvertimeHours.toFixed(2),
  };
});

/**
 * Estado seleccionado actualmente en el formulario lateral.
 */
const selectedStatus = computed(() => {
  return (
    props.dayStatuses.find((status) => {
      return Number(status.id) === Number(dayForm.status_id);
    }) ?? null
  );
});

/**
 * Indica si el estado seleccionado requiere hora de ingreso y salida.
 */
const selectedStatusRequiresHours = computed(() => {
  return statusRequiresHours(selectedStatus.value?.code);
});

const selectedStatusIsExchange = computed(() => {
  return selectedStatus.value?.code === "EXCHANGE_WORKED";
});

const absenceOptions = computed(() => {
  return props.attendance.days.filter((day) => {
    if (day.id === selectedDay.value?.id) {
      return false;
    }

    if (day.status?.code !== "ABSENT") {
      return false;
    }

    const exchange = day.absence_exchange;

    return (
      !exchange ||
      Number(exchange.exchange_attendance_day_id) === Number(selectedDay.value?.id)
    );
  });
});

/**
 * Convierte la fecha tecnica recibida desde Laravel a fecha local.
 */
const parseLocalDate = (dateString) => {
  return new Date(`${dateString}T00:00:00`);
};

/**
 * Normaliza una hora proveniente de BD.
 *
 * Ejemplos:
 * - 08:00:00 -> 08:00
 * - 08:00 -> 08:00
 * - null -> ''
 */
const normalizeTime = (time) => {
  if (!time) {
    return "";
  }

  return String(time).slice(0, 5);
};

/**
 * Formatea horas numéricas.
 */
const formatHours = (hours) => {
  return Number(hours ?? 0).toLocaleString("es-PE", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

/**
 * Formatea hora para mostrar.
 */
const formatTime = (time) => {
  return time ? normalizeTime(time) : "--:--";
};

/**
 * Indica si un estado representa trabajo efectivo.
 */
const statusRequiresHours = (statusCode) => {
  return ["PRESENT", "EXCHANGE_WORKED"].includes(statusCode);
};

/**
 * Indica si un día pertenece al futuro.
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
 */
const canEditDay = (day) => {
  return !day.blank && canEdit.value && !isFutureDay(day);
};

/**
 * Días seleccionados en modo selección múltiple.
 */
const selectedDays = computed(() => {
  return props.attendance.days.filter((day) => {
    return selectedDayIds.value.includes(day.id);
  });
});

/**
 * Indica si un día está seleccionado en modo masivo.
 */
const isDaySelected = (day) => {
  return selectedDayIds.value.includes(day.id);
};

/**
 * Activa o desactiva el modo de selección múltiple.
 */
const toggleBulkMode = () => {
  bulkMode.value = !bulkMode.value;
  selectedDayIds.value = [];
  selectedDay.value = null;
  bulkForm.clearErrors();
};

/**
 * Selecciona o deselecciona un día para actualización masiva.
 */
const toggleDaySelection = (day) => {
  if (!canEditDay(day)) {
    return;
  }

  if (selectedDayIds.value.includes(day.id)) {
    selectedDayIds.value = selectedDayIds.value.filter((id) => id !== day.id);
    return;
  }

  selectedDayIds.value = [...selectedDayIds.value, day.id];
};

/**
 * Maneja el clic de una celda del calendario.
 *
 * - En modo normal: abre edición individual.
 * - En modo selección múltiple: selecciona o deselecciona el día.
 */
const handleDayClick = (day) => {
  if (bulkMode.value) {
    toggleDaySelection(day);
    return;
  }

  openDay(day);
};

/**
 * Limpia la selección múltiple.
 */
const clearSelection = () => {
  selectedDayIds.value = [];
  bulkForm.clearErrors();
};

/**
 * Aplica un mismo estado a todos los días seleccionados.
 */
const applyBulkStatus = (statusCode) => {
  if (selectedDayIds.value.length === 0 || bulkForm.processing) {
    return;
  }

  bulkForm.day_ids = [...selectedDayIds.value];
  bulkForm.status_code = statusCode;

  bulkForm.patch(route("attendance.days.bulk-update", props.attendance.id), {
    preserveScroll: true,

    onSuccess: () => {
      selectedDayIds.value = [];
      bulkForm.reset();
    },
  });
};

/**
 * Abre el formulario lateral con los datos del día seleccionado.
 */
const openDay = (day) => {
  if (!canEditDay(day)) {
    return;
  }

  selectedDay.value = day;

  dayForm.status_id = day.status?.id ?? "";
  dayForm.entry_time = normalizeTime(day.entry_time);
  dayForm.exit_time = normalizeTime(day.exit_time);
  dayForm.absence_attendance_day_id =
    day.worked_exchange?.absence_attendance_day_id ?? "";
  dayForm.observation = day.observation ?? "";
  dayForm.clearErrors();
};

/**
 * Guarda los cambios del día seleccionado.
 */
const updateDay = () => {
  if (!selectedDay.value || !canEdit.value) {
    return;
  }

  dayForm.patch(route("attendance.days.update", selectedDay.value.id), {
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
const closeAttendance = async () => {
  const confirmed = await confirmCloseAttendance({
    employeeName: props.attendance.employee.name,
    period: props.attendance.period,
  });

  if (!confirmed) {
    return;
  }

  router.patch(
    route("attendance.close", props.attendance.id),
    {},
    {
      preserveScroll: true,
    }
  );
};

const reopenAttendance = async () => {
  const confirmed = await confirmAction({
    title: "Reabrir asistencia",
    text: `Se habilitara la edicion de ${props.attendance.employee.name} para ${props.attendance.period}.`,
    icon: "warning",
    confirmButtonText: "Reabrir asistencia",
  });

  if (!confirmed) {
    return;
  }

  router.patch(
    route("attendance.reopen", props.attendance.id),
    {},
    {
      preserveScroll: true,
    }
  );
};

/**
 * Clases visuales para cada celda del calendario.
 */
const dayCellClasses = (day) => {
  if (day.blank) {
    return "cursor-default bg-slate-50 text-slate-300";
  }

  if (isFutureDay(day)) {
    return "cursor-not-allowed bg-slate-50 text-slate-400";
  }

  const classes = {
    UNMARKED: "bg-white text-slate-700 hover:bg-slate-50",
    PRESENT: "bg-emerald-50 text-emerald-800 hover:bg-emerald-100",
    ABSENT: "bg-red-50 text-red-800 hover:bg-red-100",
    EXCHANGE_WORKED: "bg-blue-50 text-blue-800 hover:bg-blue-100",
    REST: "bg-amber-50 text-amber-800 hover:bg-amber-100",
  };

  return classes[day.status?.code] ?? classes.UNMARKED;
};

/**
 * Clases visuales para el badge del estado diario.
 */
const dayBadgeClasses = (status) => {
  const classes = {
    UNMARKED: "bg-slate-100 text-slate-600 ring-slate-200",
    PRESENT: "bg-emerald-100 text-emerald-700 ring-emerald-200",
    ABSENT: "bg-red-100 text-red-700 ring-red-200",
    EXCHANGE_WORKED: "bg-blue-100 text-blue-700 ring-blue-200",
    REST: "bg-amber-100 text-amber-700 ring-amber-200",
  };

  return classes[status?.code] ?? classes.UNMARKED;
};

/**
 * Texto corto del estado diario.
 */
const shortStatusLabel = (status) => {
  const labels = {
    UNMARKED: "Sin marcar",
    PRESENT: "Asistió",
    ABSENT: "Faltó",
    EXCHANGE_WORKED: "Canje",
    REST: "Descanso",
  };

  return labels[status?.code] ?? status?.name ?? "Sin marcar";
};

/**
 * Clases visuales para el estado mensual.
 */
const monthlyStatusClasses = computed(() => {
  if (props.attendance.status?.code === "CLOSED") {
    return "border-emerald-200 bg-emerald-50 text-emerald-700";
  }

  return "border-amber-200 bg-amber-50 text-amber-700";
});
</script>

<template>
  <Head :title="`Calendario - ${attendance.period}`" />

  <AuthenticatedLayout title="Calendario de asistencia">
    <section class="space-y-5">
      <PageHeader
        :title="`Calendario de asistencia - ${attendance.period}`"
        description="Registra la asistencia diaria del trabajador de forma ordenada."
      >
        <template #icon>
          <CalendarCheck class="h-7 w-7" />
        </template>

        <template #actions>
          <div class="flex flex-wrap items-center gap-3">
            <Link
              :href="route('attendance.index')"
              class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50"
            >
              <ArrowLeft class="h-4 w-4" />
              Volver
            </Link>

            <button
              v-if="canEdit"
              type="button"
              class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white shadow transition hover:bg-primary-dark"
              @click="closeAttendance"
            >
              <CheckCircle2 class="h-4 w-4" />
              Cerrar asistencia
            </button>

            <button
              v-if="attendance.can_reopen && can('attendance.reopen')"
              type="button"
              class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-700 shadow-sm transition hover:bg-amber-100"
              @click="reopenAttendance"
            >
              <RefreshCcw class="h-4 w-4" />
              Reabrir asistencia
            </button>
          </div>
        </template>
      </PageHeader>

      <!-- Contexto principal, contadores y reglas -->
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
        <!-- Control mensual compacto -->
        <SectionCard
          title="Control mensual"
          description="Información del trabajador, periodo, estado actual y resumen de asistencia."
        >
          <div
            class="grid gap-4 xl:grid-cols-[minmax(250px,0.72fr)_minmax(0,1.28fr)] xl:items-start"
          >
            <!-- Información del trabajador -->
            <div class="flex min-w-0 gap-3">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
              >
                <UserCheck class="h-5 w-5" />
              </div>

              <div class="min-w-0 flex-1">
                <h2 class="text-lg font-black leading-tight text-gray-900">
                  {{ attendance.employee.name }}
                </h2>

                <p class="mt-1 text-xs font-bold text-gray-500">
                  Doc. {{ attendance.employee.document }}
                  <span class="mx-1 text-slate-300">•</span>
                  Cód. {{ attendance.employee.code }}
                </p>

                <div
                  class="mt-2 grid gap-x-4 gap-y-1 text-[11px] font-semibold leading-tight text-gray-600 sm:grid-cols-2"
                >
                  <p class="break-words">
                    <span class="font-black text-gray-700">Cargo:</span>
                    {{ attendance.employee.position || "Sin cargo" }}
                  </p>

                  <p class="break-words">
                    <span class="font-black text-gray-700">Área:</span>
                    {{ attendance.employee.work_area || "Sin área" }}
                  </p>

                  <p class="break-words">
                    <span class="font-black text-gray-700">Estado:</span>
                    {{ attendance.employee.employment_status || "Sin estado" }}
                  </p>

                  <p class="break-words">
                    <span class="font-black text-gray-700">Régimen:</span>
                    {{ attendance.employee.pension_system || "Sin régimen" }}
                  </p>
                </div>

                <div class="mt-2 flex flex-wrap gap-1.5">
                  <span
                    class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-black"
                    :class="monthlyStatusClasses"
                  >
                    {{ attendance.status.name }}
                  </span>

                  <span
                    class="inline-flex rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-black text-primary"
                  >
                    {{ attendance.period }}
                  </span>

                  <span
                    v-if="attendance.employee.work_shift"
                    class="inline-flex rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-black text-blue-700"
                  >
                    {{ attendance.employee.work_shift.name }}

                    <template v-if="attendance.employee.work_shift.rotation_enabled">
                      · {{ attendance.employee.work_shift.rotation_work_days }}x{{
                        attendance.employee.work_shift.rotation_rest_days
                      }}
                    </template>
                  </span>
                </div>
              </div>
            </div>

            <!-- Contadores compactos sin ocultar texto -->
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
              <div
                class="min-h-[64px] rounded-xl border border-primary/20 bg-primary/5 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-primary"
                >
                  Sin marcar
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-primary">
                  {{ totals.unmarkedDays }}
                </p>
              </div>

              <div
                class="min-h-[64px] rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-emerald-700"
                >
                  Asistió
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-emerald-700">
                  {{ totals.workedDays }}
                </p>
              </div>

              <div
                class="min-h-[64px] rounded-xl border border-red-200 bg-red-50 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-red-700"
                >
                  Faltó
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-red-700">
                  {{ totals.absenceDays }}
                </p>
              </div>

              <div
                class="min-h-[64px] rounded-xl border border-blue-200 bg-blue-50 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-blue-700"
                >
                  Canjes
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-blue-700">
                  {{ totals.exchangeDays }}
                </p>
              </div>

              <div
                class="min-h-[64px] rounded-xl border border-rose-200 bg-rose-50 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-rose-700"
                >
                  Por descontar
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-rose-700">
                  {{ attendance.uncompensated_absence_days ?? totals.absenceDays }}
                </p>
              </div>

              <div
                class="min-h-[64px] rounded-xl border border-amber-200 bg-amber-50 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-amber-700"
                >
                  Descanso
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-amber-700">
                  {{ totals.restDays }}
                </p>
              </div>

              <div
                class="min-h-[64px] rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-slate-600"
                >
                  H. extras
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-slate-700">
                  {{ totals.overtimeHours }}
                </p>
              </div>

              <div
                class="min-h-[64px] rounded-xl border border-lime-200 bg-lime-50 px-3 py-2"
              >
                <p
                  class="text-[10px] font-black uppercase leading-tight tracking-wide text-lime-700"
                >
                  H. pago
                </p>

                <p class="mt-1 text-2xl font-black leading-none text-lime-700">
                  {{ totals.payableOvertimeHours }}
                </p>
              </div>
            </div>
          </div>
        </SectionCard>

        <!-- Reglas rápidas compactas -->
        <SectionCard
          title="Reglas rápidas"
          description="Antes de marcar asistencia, considera estas reglas."
        >
          <div class="space-y-2">
            <div class="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
              <Ban class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" />

              <p class="text-xs font-semibold leading-relaxed text-slate-600">
                No se pueden marcar días futuros.
              </p>
            </div>

            <div class="flex gap-3 rounded-xl border border-blue-200 bg-blue-50 p-3">
              <Clock3 class="mt-0.5 h-4 w-4 shrink-0 text-blue-700" />

              <p class="text-xs font-semibold leading-relaxed text-blue-800">
                Asistió o canje requieren ingreso y salida.
              </p>
            </div>

            <div class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
              <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />

              <p class="text-xs font-semibold leading-relaxed text-amber-800">
                Las horas extras se calculan con el turno asignado.
              </p>
            </div>
          </div>
        </SectionCard>
      </div>

      <!-- Calendario y formulario lateral -->
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <!-- Calendario -->
        <SectionCard
          title="Calendario mensual"
          description="Haz clic en un día disponible para registrar o corregir su asistencia."
        >
          <!-- Leyenda compacta -->
          <div class="mb-4 flex flex-wrap gap-2">
            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-600"
            >
              <span class="h-2 w-2 rounded-full bg-slate-400"></span>
              Sin marcar
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-black text-emerald-700"
            >
              <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
              Asistió
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-black text-red-700"
            >
              <span class="h-2 w-2 rounded-full bg-red-500"></span>
              Faltó
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-black text-blue-700"
            >
              <span class="h-2 w-2 rounded-full bg-blue-500"></span>
              Canje
            </span>

            <span
              class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-black text-amber-700"
            >
              <span class="h-2 w-2 rounded-full bg-amber-500"></span>
              Descanso
            </span>
          </div>

          <!-- Barra de selección múltiple -->
          <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div
              class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
              <div>
                <p class="text-sm font-black text-gray-900">
                  Registro rápido por selección
                </p>

                <p class="text-xs font-semibold text-gray-500">
                  Activa la selección múltiple, elige varios días y aplica un solo estado.
                </p>

                <InputError
                  class="mt-2"
                  :message="bulkForm.errors.day_ids || bulkForm.errors.status_code"
                />
              </div>

              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  class="rounded-xl border px-3 py-2 text-xs font-black shadow-sm transition"
                  :class="
                    bulkMode
                      ? 'border-primary bg-primary text-white'
                      : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100'
                  "
                  @click="toggleBulkMode"
                >
                  {{ bulkMode ? "Salir de selección" : "Seleccionar varios" }}
                </button>

                <button
                  v-if="bulkMode && selectedDayIds.length > 0"
                  type="button"
                  class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 shadow-sm transition hover:bg-slate-100"
                  @click="clearSelection"
                >
                  Limpiar
                </button>
              </div>
            </div>

            <div
              v-if="bulkMode"
              class="mt-3 flex flex-col gap-3 border-t border-slate-200 pt-3 lg:flex-row lg:items-center lg:justify-between"
            >
              <p class="text-sm font-bold text-slate-600">
                {{ selectedDayIds.length }} día(s) seleccionado(s)
              </p>

              <div class="flex flex-wrap gap-2">
                <button
                  type="button"
                  class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50"
                  :disabled="selectedDayIds.length === 0 || bulkForm.processing"
                  @click="applyBulkStatus('PRESENT')"
                >
                  Marcar asistió
                </button>

                <button
                  type="button"
                  class="rounded-xl bg-red-600 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:bg-red-700 disabled:opacity-50"
                  :disabled="selectedDayIds.length === 0 || bulkForm.processing"
                  @click="applyBulkStatus('ABSENT')"
                >
                  Marcar faltó
                </button>

                <button
                  type="button"
                  class="rounded-xl bg-amber-500 px-3 py-2 text-xs font-black text-white shadow-sm transition hover:bg-amber-600 disabled:opacity-50"
                  :disabled="selectedDayIds.length === 0 || bulkForm.processing"
                  @click="applyBulkStatus('REST')"
                >
                  Marcar descanso
                </button>
              </div>
            </div>
          </div>

          <!-- Calendario compacto sin scroll horizontal -->
          <div
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
          >
            <!-- Cabecera de días -->
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-100">
              <div
                v-for="weekDay in weekDays"
                :key="weekDay"
                class="px-2 py-2 text-center text-[11px] font-black uppercase tracking-wide text-slate-500"
              >
                {{ weekDay }}
              </div>
            </div>

            <!-- Celdas del calendario -->
            <div class="grid grid-cols-7">
              <button
                v-for="cell in calendarCells"
                :key="cell.id"
                type="button"
                class="min-h-[92px] border-b border-r border-slate-200 p-2 text-left transition"
                :class="[
                  dayCellClasses(cell),
                  selectedDay?.id === cell.id || isDaySelected(cell)
                    ? 'relative z-10 ring-2 ring-primary ring-inset'
                    : '',
                  !canEditDay(cell) && !cell.blank ? 'opacity-80' : '',
                ]"
                :disabled="!canEditDay(cell)"
                @click="handleDayClick(cell)"
              >
                <template v-if="!cell.blank">
                  <!-- Número del día -->
                  <div class="flex items-start justify-between gap-1">
                    <div>
                      <p class="text-xl font-black leading-none">
                        {{ Number(cell.day_number) }}
                      </p>

                      <p
                        class="mt-1 text-[10px] font-black uppercase leading-none opacity-70"
                      >
                        {{ cell.weekday }}
                      </p>
                    </div>

                    <Ban v-if="isFutureDay(cell)" class="h-3.5 w-3.5 text-slate-400" />
                  </div>

                  <!-- Estado -->
                  <div class="mt-3">
                    <span
                      v-if="!isFutureDay(cell)"
                      class="inline-flex max-w-full rounded-full px-2 py-0.5 text-[10px] font-black ring-1"
                      :class="dayBadgeClasses(cell.status)"
                    >
                      {{ shortStatusLabel(cell.status) }}
                    </span>

                    <span
                      v-else
                      class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-black text-slate-500"
                    >
                      Futuro
                    </span>
                  </div>

                  <!-- Datos adicionales -->
                  <div class="mt-2 space-y-1">
                    <p
                      v-if="statusRequiresHours(cell.status?.code)"
                      class="text-[10px] font-bold leading-none text-slate-600"
                    >
                      {{ formatTime(cell.entry_time) }} -
                      {{ formatTime(cell.exit_time) }}
                    </p>

                    <p
                      v-if="Number(cell.overtime_hours) > 0"
                      class="inline-flex rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-black shadow-sm"
                    >
                      HE:
                      {{ formatHours(cell.overtime_hours) }}
                    </p>

                    <p
                      v-if="Number(cell.payable_overtime_hours) > 0"
                      class="inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-black text-primary shadow-sm"
                    >
                      Pago:
                      {{ formatHours(cell.payable_overtime_hours) }}
                    </p>

                    <p
                      v-if="cell.status?.code === 'EXCHANGE_WORKED'"
                      class="line-clamp-1 rounded-lg bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold text-blue-800 shadow-sm"
                    >
                      {{
                        cell.worked_exchange
                          ? `Compensa: ${formatDate(cell.worked_exchange.absence_date)}`
                          : "Falta por vincular"
                      }}
                    </p>

                    <p
                      v-if="cell.observation"
                      class="line-clamp-1 rounded-lg bg-white/80 px-1.5 py-0.5 text-[10px] font-semibold shadow-sm"
                    >
                      {{ cell.observation }}
                    </p>
                  </div>
                </template>
              </button>
            </div>
          </div>
        </SectionCard>

        <!-- Panel lateral de edición -->
        <SectionCard
          title="Editar día"
          description="Selecciona un día disponible para registrar su estado."
          class="xl:sticky xl:top-6 xl:self-start"
        >
          <div
            v-if="!selectedDay"
            class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center"
          >
            <CalendarDays class="mx-auto h-10 w-10 text-primary" />

            <h3 class="mt-4 text-lg font-black text-gray-900">Selecciona un día</h3>

            <p class="mt-2 text-sm leading-relaxed text-gray-500">
              Elige un día disponible del calendario. Los días futuros y asistencias
              cerradas no se pueden editar.
            </p>
          </div>

          <form v-else class="space-y-4" @submit.prevent="updateDay">
            <!-- Día seleccionado -->
            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
              <p class="text-xs font-black uppercase tracking-wide text-primary">
                Día seleccionado
              </p>

              <p class="mt-1 text-lg font-black text-gray-900">
                {{ formatDate(selectedDay.attendance_date) }}
              </p>

              <p class="text-sm font-semibold text-gray-500">
                Estado actual: {{ selectedDay.status.name }}
              </p>
            </div>

            <!-- Estado del día -->
            <div>
              <InputLabel for="status_id" value="Estado del día" required />

              <select
                id="status_id"
                v-model="dayForm.status_id"
                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                :disabled="!canEdit"
                required
              >
                <option value="">Selecciona un estado</option>

                <option v-for="status in dayStatuses" :key="status.id" :value="status.id">
                  {{ status.name }}
                </option>
              </select>

              <InputError class="mt-2" :message="dayForm.errors.status_id" />
            </div>

            <div
              v-if="selectedStatusIsExchange"
              class="rounded-2xl border border-blue-200 bg-blue-50 p-4"
            >
              <InputLabel for="absence_attendance_day_id" value="Falta que compensa" optional />

              <select
                id="absence_attendance_day_id"
                v-model="dayForm.absence_attendance_day_id"
                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                :disabled="!canEdit"
                required
              >
                <option value="">Selecciona la falta a compensar</option>

                <option
                  v-for="absence in absenceOptions"
                  :key="absence.id"
                  :value="absence.id"
                >
                  {{ formatDate(absence.attendance_date) }} -
                  {{ absence.observation || "Falta registrada" }}
                </option>
              </select>

              <InputError
                class="mt-2"
                :message="dayForm.errors.absence_attendance_day_id"
              />

              <p
                v-if="absenceOptions.length === 0"
                class="mt-2 text-xs font-semibold text-blue-800"
              >
                Primero marca una fecha del mes como Faltó. Luego vuelve a este día y
                selecciona esa falta para crear el canje formal.
              </p>
            </div>

            <!-- Horas reales -->
            <div
              v-if="selectedStatusRequiresHours"
              class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
            >
              <div class="mb-3 flex items-start gap-2">
                <Clock3 class="mt-0.5 h-5 w-5 shrink-0 text-primary" />

                <div>
                  <p class="text-sm font-black text-gray-900">Horario registrado</p>

                  <p class="text-xs font-semibold leading-relaxed text-gray-500">
                    Ingresa las horas transcritas del cuaderno. El sistema calculará horas
                    trabajadas y extras.
                  </p>
                </div>
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <div>
                  <InputLabel for="entry_time" value="Hora de ingreso" optional />

                  <input
                    id="entry_time"
                    v-model="dayForm.entry_time"
                    type="time"
                    class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                    :disabled="!canEdit"
                    required
                  />

                  <InputError class="mt-2" :message="dayForm.errors.entry_time" />
                </div>

                <div>
                  <InputLabel for="exit_time" value="Hora de salida" optional />

                  <input
                    id="exit_time"
                    v-model="dayForm.exit_time"
                    type="time"
                    class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                    :disabled="!canEdit"
                    required
                  />

                  <InputError class="mt-2" :message="dayForm.errors.exit_time" />
                </div>
              </div>
            </div>

            <!-- Resumen calculado del día actual -->
            <div
              v-if="
                selectedDay.worked_hours ||
                selectedDay.overtime_hours ||
                selectedDay.payable_overtime_hours
              "
              class="grid gap-3 sm:grid-cols-3"
            >
              <div class="rounded-2xl border border-slate-200 bg-white p-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">
                  H. trabajadas
                </p>

                <p class="mt-1 text-lg font-black text-slate-800">
                  {{ formatHours(selectedDay.worked_hours) }}
                </p>
              </div>

              <div class="rounded-2xl border border-primary/20 bg-primary/5 p-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-primary">
                  H. extras
                </p>

                <p class="mt-1 text-lg font-black text-primary">
                  {{ formatHours(selectedDay.overtime_hours) }}
                </p>
              </div>

              <div class="rounded-2xl border border-blue-200 bg-blue-50 p-3">
                <p class="text-[11px] font-black uppercase tracking-wide text-blue-700">
                  H. extra pago
                </p>

                <p class="mt-1 text-lg font-black text-blue-700">
                  {{ formatHours(selectedDay.payable_overtime_hours) }}
                </p>
              </div>
            </div>

            <!-- Aviso cuando no requiere horas -->
            <div
              v-if="!selectedStatusRequiresHours"
              class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
            >
              <div class="flex items-start gap-3">
                <Info class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" />

                <p class="text-xs font-semibold leading-relaxed text-slate-600">
                  Este estado no requiere hora de ingreso ni salida. Si guardas este día,
                  el sistema limpiará las horas trabajadas y horas extras.
                </p>
              </div>
            </div>

            <!-- Observación -->
            <div>
              <InputLabel for="observation" value="Observación" optional />

              <textarea
                id="observation"
                v-model="dayForm.observation"
                rows="3"
                class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                :disabled="!canEdit"
                placeholder="Observación opcional del día."
              />

              <InputError class="mt-2" :message="dayForm.errors.observation" />
            </div>

            <!-- Ayuda contextual -->
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
              <div class="flex items-start gap-3">
                <RefreshCcw class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                <p class="text-xs font-semibold leading-relaxed text-blue-800">
                  Si este día compensará una falta, márcalo como “Trabajó como canje”.
                  Después vincularemos este día con la fecha exacta de la falta.
                </p>
              </div>
            </div>

            <!-- Acciones -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 shadow-sm transition hover:bg-slate-50"
                @click="closeDayPanel"
              >
                Cancelar
              </button>

              <button
                v-if="canEdit"
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                :disabled="dayForm.processing"
              >
                <Save class="h-4 w-4" />

                {{ dayForm.processing ? "Guardando..." : "Guardar día" }}
              </button>
            </div>
          </form>
        </SectionCard>
      </div>
    </section>
  </AuthenticatedLayout>
</template>
