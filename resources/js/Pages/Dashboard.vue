<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Users,
    CalendarCheck,
    FileSpreadsheet,
    ReceiptText,
    Clock3,
    AlertTriangle,
    CheckCircle2,
    Activity,
    ArrowUpRight,
} from 'lucide-vue-next';

/**
 * Datos estáticos temporales.
 * Luego estos valores vendrán desde el controlador DashboardController.
 */
const stats = [
    {
        title: 'Trabajadores',
        value: 35,
        description: 'Personal registrado',
        icon: Users,
        color: 'text-primary',
        bg: 'bg-primary/10',
        border: 'bg-primary',
    },
    {
        title: 'Asistencia',
        value: '94%',
        description: 'Promedio del mes',
        icon: CalendarCheck,
        color: 'text-secondary',
        bg: 'bg-secondary/10',
        border: 'bg-secondary',
    },
    {
        title: 'Planillas',
        value: 2,
        description: 'Generadas este mes',
        icon: FileSpreadsheet,
        color: 'text-primary-dark',
        bg: 'bg-primary-dark/10',
        border: 'bg-primary-dark',
    },
    {
        title: 'Boletas',
        value: 35,
        description: 'Emitidas',
        icon: ReceiptText,
        color: 'text-danger',
        bg: 'bg-danger/10',
        border: 'bg-danger',
    },
];

const monthlySummary = [
    {
        title: 'Horas extras',
        value: '52 h',
        description: 'Horas acumuladas en el mes',
        icon: Clock3,
        color: 'text-secondary',
        bg: 'bg-secondary/10',
    },
    {
        title: 'Faltas registradas',
        value: 8,
        description: 'Faltas durante el periodo actual',
        icon: AlertTriangle,
        color: 'text-danger',
        bg: 'bg-danger/10',
    },
];

const payrolls = [
    {
        period: 'Abril 2026',
        status: 'Aprobada',
        total: 'S/ 18,500.00',
        date: '30/04/2026',
    },
    {
        period: 'Marzo 2026',
        status: 'Pagada',
        total: 'S/ 17,900.00',
        date: '31/03/2026',
    },
    {
        period: 'Febrero 2026',
        status: 'Pagada',
        total: 'S/ 18,120.00',
        date: '29/02/2026',
    },
];

const recentActivities = [
    'Se generó la planilla correspondiente a Abril 2026.',
    'Se registraron 5 horas extras para trabajadores de planta.',
    'Se emitieron 35 boletas de pago.',
    'Gerencia aprobó la última planilla generada.',
];

const attendanceStatus = [
    {
        label: 'Asistencias',
        value: 94,
        color: 'bg-primary',
    },
    {
        label: 'Faltas',
        value: 6,
        color: 'bg-danger',
    },
];
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Banner principal -->
            <section
                class="overflow-hidden rounded-3xl bg-gradient-to-r from-primary via-primary to-primary-dark shadow-lg">
                <div class="relative p-6 text-white sm:p-8">
                    <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10"></div>
                    <div class="absolute -bottom-16 right-20 h-44 w-44 rounded-full bg-secondary/20"></div>

                    <div class="relative">
                        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">
                            Panel administrativo
                        </p>

                        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">
                            Bienvenido al sistema MOLICENTE
                        </h1>

                        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-white/90 sm:text-base">
                            Gestiona la asistencia mensual, genera planillas y administra
                            boletas de pago de forma ordenada y segura.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Tarjetas principales -->
            <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <article v-for="item in stats" :key="item.title"
                    class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="h-1.5" :class="item.border"></div>

                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    {{ item.title }}
                                </p>

                                <h3 class="mt-3 text-4xl font-black tracking-tight" :class="item.color">
                                    {{ item.value }}
                                </h3>

                                <p class="mt-2 text-sm text-gray-500">
                                    {{ item.description }}
                                </p>
                            </div>

                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl"
                                :class="[item.bg, item.color]">
                                <component :is="item.icon" class="h-6 w-6" />
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Resumen mensual -->
            <section class="grid gap-6 lg:grid-cols-3">
                <article v-for="item in monthlySummary" :key="item.title"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ item.title }}
                            </p>

                            <h3 class="mt-3 text-4xl font-black" :class="item.color">
                                {{ item.value }}
                            </h3>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl"
                            :class="[item.bg, item.color]">
                            <component :is="item.icon" class="h-6 w-6" />
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-gray-500">
                        {{ item.description }}
                    </p>
                </article>

                <!-- Estado asistencia -->
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Estado de asistencia
                            </p>

                            <h3 class="mt-3 text-4xl font-black text-gray-800">
                                94%
                            </h3>
                        </div>

                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Activity class="h-6 w-6" />
                        </div>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div v-for="item in attendanceStatus" :key="item.label">
                            <div class="mb-1 flex justify-between text-sm">
                                <span class="text-gray-600">
                                    {{ item.label }}
                                </span>
                                <span class="font-semibold text-gray-700">
                                    {{ item.value }}%
                                </span>
                            </div>

                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full" :class="item.color" :style="{ width: `${item.value}%` }">
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Planillas y actividad -->
            <section class="grid gap-6 xl:grid-cols-3">
                <!-- Últimas planillas -->
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md xl:col-span-2">
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">
                                Últimas planillas generadas
                            </h3>
                            <p class="text-sm text-gray-500">
                                Resumen de los últimos periodos procesados
                            </p>
                        </div>

                        <button type="button"
                            class="hidden items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-dark sm:flex">
                            Ver todas
                            <ArrowUpRight class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50 text-sm text-gray-500">
                                    <th class="px-6 py-3 font-semibold">
                                        Periodo
                                    </th>
                                    <th class="px-6 py-3 font-semibold">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 font-semibold">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 font-semibold">
                                        Fecha
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="payroll in payrolls" :key="payroll.period"
                                    class="text-sm transition hover:bg-slate-50">
                                    <td class="px-6 py-4 font-semibold text-gray-700">
                                        {{ payroll.period }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold" :class="payroll.status === 'Aprobada'
                                            ? 'bg-secondary/15 text-secondary'
                                            : 'bg-primary/15 text-primary'">
                                            {{ payroll.status }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 font-semibold text-gray-700">
                                        {{ payroll.total }}
                                    </td>

                                    <td class="px-6 py-4 text-gray-500">
                                        {{ payroll.date }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <!-- Actividad reciente -->
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md">
                    <div class="mb-5">
                        <h3 class="text-lg font-bold text-gray-800">
                            Actividad reciente
                        </h3>

                        <p class="text-sm text-gray-500">
                            Últimos movimientos registrados
                        </p>
                    </div>

                    <ul class="space-y-4">
                        <li v-for="activity in recentActivities" :key="activity"
                            class="flex gap-3 text-sm text-gray-600">
                            <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                            <span>{{ activity }}</span>
                        </li>
                    </ul>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
