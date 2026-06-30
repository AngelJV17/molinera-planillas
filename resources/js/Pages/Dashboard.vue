<script setup>
import { Head, Link } from "@inertiajs/vue3";
import {
    Activity,
    AlertTriangle,
    ArrowUpRight,
    CalendarCheck,
    CheckCircle2,
    Clock3,
    FileSpreadsheet,
    Hourglass,
    ReceiptText,
    UserRoundCheck,
    Users,
} from "lucide-vue-next";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PageHeader from "@/Components/Common/PageHeader.vue";
import SectionCard from "@/Components/Common/SectionCard.vue";

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({}),
    },

    latestPayrolls: {
        type: Array,
        default: () => [],
    },

    recentActivities: {
        type: Array,
        default: () => [],
    },

    attendanceStatus: {
        type: Array,
        default: () => [],
    },
});

const money = (amount) =>
    `S/ ${Number(amount ?? 0).toLocaleString("es-PE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;

const percent = (value) => {
    const number = Number(value ?? 0);

    if (Number.isNaN(number)) {
        return 0;
    }

    return Math.min(Math.max(number, 0), 100);
};

const hasClosedAttendances = () => {
    return Number(props.metrics.closed_attendances ?? 0) > 0;
};

const hasPendingAttendances = () => {
    return Number(props.metrics.pending_attendances ?? 0) > 0;
};

const stats = [
    {
        title: "Trabajadores",
        value: props.metrics.active_workers ?? 0,
        description: "Personal activo",
        icon: Users,
        color: "text-primary",
        bg: "bg-primary/10",
    },
    {
        title: "Asistencias cerradas",
        value: `${props.metrics.closed_attendances ?? 0} / ${props.metrics.monthly_attendances ?? 0}`,
        description: `${props.metrics.closed_attendance_rate ?? 0}% consolidado`,
        icon: CalendarCheck,
        color: "text-secondary",
        bg: "bg-secondary/10",
    },
    {
        title: "Planillas",
        value: props.metrics.current_month_payrolls ?? 0,
        description: "Generadas este mes",
        icon: FileSpreadsheet,
        color: "text-primary-dark",
        bg: "bg-primary-dark/10",
    },
    {
        title: "Pagos",
        value: props.metrics.paid_payrolls ?? 0,
        description: "Planillas pagadas",
        icon: ReceiptText,
        color: "text-danger",
        bg: "bg-danger/10",
    },
];

const monthlySummary = [
    {
        title: "Pendientes de cierre",
        value: props.metrics.pending_attendances ?? 0,
        description: "Asistencias abiertas",
        icon: Hourglass,
        color: "text-amber-700",
        bg: "bg-amber-100",
    },
    {
        title: "Sin asistencia creada",
        value: props.metrics.workers_without_attendance ?? 0,
        description: "Trabajadores sin control mensual",
        icon: UserRoundCheck,
        color: "text-blue-700",
        bg: "bg-blue-100",
    },
    {
        title: "Horas extras",
        value: `${props.metrics.overtime_hours ?? 0} h`,
        description: "En asistencias cerradas",
        icon: Clock3,
        color: "text-secondary",
        bg: "bg-secondary/10",
    },
    {
        title: "Faltas",
        value: props.metrics.absence_days ?? 0,
        description: "Faltas consolidadas",
        icon: AlertTriangle,
        color: "text-danger",
        bg: "bg-danger/10",
    },
];

const statusClass = (statusCode) =>
    ({
        IN_REVIEW: "bg-amber-100 text-amber-800",
        APPROVED: "bg-primary/15 text-primary",
        REJECTED: "bg-danger/15 text-danger",
        PAID: "bg-emerald-100 text-emerald-800",
    })[statusCode] ?? "bg-slate-100 text-slate-700";
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout title="Dashboard">
        <section class="space-y-6">
            <PageHeader
                title="Dashboard"
                description="Resumen consolidado de asistencia, planillas y actividad operativa del sistema."
            >
                <template #icon>
                    <Activity class="h-7 w-7" />
                </template>

                <template #actions>
                    <div class="flex flex-wrap items-center gap-3">
                        <Link
                            :href="route('attendance.index')"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white shadow transition hover:bg-primary-dark"
                        >
                            Ver asistencias
                            <ArrowUpRight class="h-4 w-4" />
                        </Link>

                        <Link
                            :href="route('payrolls.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-gray-600 shadow-sm transition hover:bg-slate-50"
                        >
                            Ver planillas
                        </Link>
                    </div>
                </template>
            </PageHeader>

            <!-- Métricas principales -->
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="item in stats"
                    :key="item.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md transition hover:bg-primary/5"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-gray-500">
                                {{ item.title }}
                            </p>

                            <h3
                                class="mt-2 text-3xl font-black tracking-tight"
                                :class="item.color"
                            >
                                {{ item.value }}
                            </h3>

                            <p class="mt-1 text-xs font-semibold text-gray-500">
                                {{ item.description }}
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            :class="[item.bg, item.color]"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <!-- Aviso contextual -->
            <section
                v-if="!hasClosedAttendances()"
                class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4"
            >
                <div class="flex gap-3">
                    <AlertTriangle
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                    />

                    <div>
                        <h3 class="text-sm font-black text-amber-900">
                            Aún no hay asistencias cerradas este mes
                        </h3>

                        <p
                            class="mt-1 text-sm font-semibold leading-relaxed text-amber-800"
                        >
                            El porcentaje de asistencia se calculará cuando
                            existan controles mensuales cerrados.
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-else-if="hasPendingAttendances()"
                class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4"
            >
                <div class="flex gap-3">
                    <Hourglass class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />

                    <div>
                        <h3 class="text-sm font-black text-blue-900">
                            El mes aún no está completamente consolidado
                        </h3>

                        <p
                            class="mt-1 text-sm font-semibold leading-relaxed text-blue-800"
                        >
                            Hay
                            {{ metrics.pending_attendances ?? 0 }} asistencia(s)
                            pendiente(s) de cierre. El porcentaje actual usa
                            solo
                            {{ metrics.closed_attendances ?? 0 }} asistencia(s)
                            cerrada(s).
                        </p>
                    </div>
                </div>
            </section>

            <!-- Resumen operativo -->
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="item in monthlySummary"
                    :key="item.title"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-gray-500">
                                {{ item.title }}
                            </p>

                            <h3
                                class="mt-2 text-3xl font-black"
                                :class="item.color"
                            >
                                {{ item.value }}
                            </h3>

                            <p class="mt-1 text-xs font-semibold text-gray-500">
                                {{ item.description }}
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            :class="[item.bg, item.color]"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            </section>

            <!-- Asistencia consolidada, avance mensual y actividad -->
            <section class="grid gap-6 xl:grid-cols-3">
                <SectionCard
                    title="Asistencia consolidada"
                    description="Calculada solo con asistencias cerradas."
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-4xl font-black text-gray-900">
                                {{ metrics.attendance_rate ?? 0 }}%
                            </h3>

                            <p class="mt-2 text-sm font-semibold text-gray-500">
                                Días evaluados:
                                {{ metrics.evaluated_attendance_days ?? 0 }}
                            </p>
                        </div>

                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <Activity class="h-6 w-6" />
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-emerald-50 p-3">
                            <p class="text-xs font-black text-emerald-700">
                                Asistió
                            </p>

                            <p class="mt-1 text-xl font-black text-emerald-700">
                                {{ metrics.worked_days ?? 0 }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-red-50 p-3">
                            <p class="text-xs font-black text-red-700">Faltó</p>

                            <p class="mt-1 text-xl font-black text-red-700">
                                {{ metrics.absence_days ?? 0 }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div v-for="item in attendanceStatus" :key="item.label">
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="font-semibold text-gray-600">
                                    {{ item.label }}
                                </span>

                                <span class="font-black text-gray-800">
                                    {{ item.value }}%
                                </span>
                            </div>

                            <div class="h-2 rounded-full bg-slate-100">
                                <div
                                    class="h-2 rounded-full"
                                    :class="item.color"
                                    :style="{
                                        width: `${percent(item.value)}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Avance de cierre mensual"
                    description="Control de asistencias cerradas y pendientes."
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-4xl font-black text-primary">
                                {{ metrics.closed_attendance_rate ?? 0 }}%
                            </h3>

                            <p class="mt-2 text-sm font-semibold text-gray-500">
                                {{ metrics.closed_attendances ?? 0 }} de
                                {{ metrics.monthly_attendances ?? 0 }} cerradas
                            </p>
                        </div>

                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <CalendarCheck class="h-6 w-6" />
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-blue-50 p-3">
                            <p class="text-xs font-black text-blue-700">
                                Pendientes
                            </p>

                            <p class="mt-1 text-xl font-black text-blue-700">
                                {{ metrics.pending_attendances ?? 0 }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-amber-50 p-3">
                            <p class="text-xs font-black text-amber-700">
                                Sin crear
                            </p>

                            <p class="mt-1 text-xl font-black text-amber-700">
                                {{ metrics.workers_without_attendance ?? 0 }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-semibold text-gray-600">
                                Mes consolidado
                            </span>

                            <span class="font-black text-gray-800">
                                {{ metrics.closed_attendance_rate ?? 0 }}%
                            </span>
                        </div>

                        <div class="h-2 rounded-full bg-slate-100">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{
                                    width: `${percent(metrics.closed_attendance_rate)}%`,
                                }"
                            ></div>
                        </div>
                    </div>

                    <p
                        class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-semibold leading-relaxed text-slate-600"
                    >
                        El porcentaje de asistencia será confiable cuando todas
                        las asistencias del mes estén cerradas.
                    </p>
                </SectionCard>

                <SectionCard
                    title="Actividad reciente"
                    description="Últimos movimientos del sistema."
                >
                    <div v-if="recentActivities.length" class="space-y-3">
                        <div
                            v-for="activity in recentActivities"
                            :key="activity"
                            class="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-gray-600"
                        >
                            <CheckCircle2
                                class="mt-0.5 h-5 w-5 shrink-0 text-primary"
                            />

                            <p class="font-semibold leading-relaxed">
                                {{ activity }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="text-sm font-semibold text-gray-500">
                        Aún no hay actividad reciente.
                    </p>
                </SectionCard>
            </section>

            <!-- Últimas planillas generadas -->
            <SectionCard
                title="Últimas planillas generadas"
                description="Resumen de los últimos periodos procesados."
            >
                <template #actions>
                    <Link
                        :href="route('payrolls.index')"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white transition hover:bg-primary-dark"
                    >
                        Ver todas
                        <ArrowUpRight class="h-4 w-4" />
                    </Link>
                </template>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-xs uppercase tracking-wide text-gray-500"
                            >
                                <th class="px-4 py-3 font-black">Periodo</th>
                                <th class="px-4 py-3 font-black">Estado</th>
                                <th class="px-4 py-3 font-black">Neto</th>
                                <th class="px-4 py-3 font-black">Fecha</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="payroll in latestPayrolls"
                                :key="payroll.id"
                                class="text-sm transition hover:bg-slate-50"
                            >
                                <td class="px-4 py-3 font-bold text-gray-700">
                                    {{ payroll.period }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-black"
                                        :class="statusClass(payroll.status_code)"
                                    >
                                        {{ payroll.status }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 font-bold text-gray-700">
                                    {{ money(payroll.total) }}
                                </td>

                                <td class="px-4 py-3 font-semibold text-gray-500">
                                    {{ payroll.date ?? "-" }}
                                </td>
                            </tr>

                            <tr v-if="latestPayrolls.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-8 text-center text-sm font-semibold text-gray-500"
                                >
                                    Aún no hay planillas registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
