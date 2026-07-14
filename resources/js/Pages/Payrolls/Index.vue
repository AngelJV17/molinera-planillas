<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    Calculator,
    CheckCircle2,
    ClipboardCheck,
    Database,
    Eye,
    FileSpreadsheet,
    Play,
    ReceiptText,
    RotateCcw,
    TriangleAlert,
    XCircle,
} from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/Common/EmptyState.vue';
import FilterCard from '@/Components/Common/FilterCard.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ListSummary from '@/Components/Common/ListSummary.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import DataTable from '@/Components/Table/DataTable.vue';
import SearchInput from '@/Components/Table/SearchInput.vue';
import TableActionButton from '@/Components/Table/TableActionButton.vue';
import TableActions from '@/Components/Table/TableActions.vue';
import TableEntityCell from '@/Components/Table/TableEntityCell.vue';
import PerPageFilter from '@/Components/Filters/PerPageFilter.vue';
import { confirmAction, promptActionReason } from '@/Utils/alerts';

const props = defineProps({
    payrolls: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statuses: {
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
    defaultPeriod: {
        type: String,
        default: '',
    },
});

const page = usePage();
const selectedPayrollId = ref(props.payrolls.data[0]?.id ?? null);
const period = ref(props.filters.period ?? '');
const statusId = ref(props.filters.status_id ?? '');
const perPage = ref(props.filters.per_page ?? 10);
let filterTimeout = null;

const permissions = computed(() => page.props.auth?.permissions ?? []);
const can = (permission) => permissions.value.includes(permission);

const form = useForm({
    month: props.defaultPeriod ? Number(props.defaultPeriod.slice(5, 7)) : new Date().getMonth() + 1,
    year: props.defaultPeriod ? Number(props.defaultPeriod.slice(0, 4)) : new Date().getFullYear(),
    payment_date: '',
    observations: '',
});

const fieldError = (field) => {
    const message = form.errors[field];

    if (!message) {
        return '';
    }

    const generalMessages = [
        form.errors.period,
        form.errors.payroll,
    ].filter(Boolean);

    return generalMessages.includes(message) ? '' : message;
};

const columns = [
    { key: 'payroll', label: 'Planilla' },
    { key: 'status', label: 'Estado' },
    { key: 'employees', label: 'Trabajadores' },
    { key: 'total', label: 'Neto' },
    { key: 'actions', label: 'Acciones', align: 'right' },
];

const detailColumns = [
    { key: 'worker', label: 'Trabajador' },
    { key: 'attendance', label: 'Asistencia' },
    { key: 'income', label: 'Ingresos' },
    { key: 'discount', label: 'Descuentos' },
    { key: 'net', label: 'Neto' },
];

const selectedPayroll = computed(() => {
    return props.payrolls.data.find((payroll) => payroll.id === selectedPayrollId.value) ?? null;
});

const totalNet = computed(() => {
    return props.payrolls.data.reduce((sum, payroll) => sum + Number(payroll.total_net ?? 0), 0);
});

const totalEmployees = computed(() => {
    return props.payrolls.data.reduce((sum, payroll) => sum + Number(payroll.employee_count ?? 0), 0);
});

const applyFilters = () => {
    router.get(
        route('payrolls.index'),
        {
            period: period.value || undefined,
            status_id: statusId.value || undefined,
            per_page: perPage.value || 10,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch([period, statusId, perPage], () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => applyFilters(), 350);
});

watch(
    () => props.payrolls.data,
    (payrolls) => {
        if (!payrolls.some((payroll) => payroll.id === selectedPayrollId.value)) {
            selectedPayrollId.value = payrolls[0]?.id ?? null;
        }
    },
);

const money = (amount) => {
    return `S/ ${Number(amount ?? 0).toLocaleString('es-PE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};

const statusClass = (statusCode) => {
    const classes = {
        IN_REVIEW: 'bg-amber-100 text-amber-800',
        OBSERVED: 'bg-blue-100 text-blue-800',
        APPROVED: 'bg-primary/15 text-primary',
        REJECTED: 'bg-danger/15 text-danger',
        PAID: 'bg-emerald-100 text-emerald-800',
    };

    return classes[statusCode] ?? 'bg-slate-100 text-slate-700';
};

const generatePayroll = async () => {
    const monthLabel = props.monthOptions.find((month) => Number(month.value) === Number(form.month))?.label;

    const confirmed = await confirmAction({
        title: '¿Generar planilla?',
        text: `Se calculará la planilla de ${monthLabel ?? 'este mes'} ${form.year} usando asistencias cerradas.`,
        icon: 'question',
        confirmButtonText: 'Sí, generar',
    });

    if (!confirmed) {
        return;
    }

    form.post(route('payrolls.store'), {
        preserveScroll: true,
    });
};

const approvePayroll = async (payroll) => {
    const confirmed = await confirmAction({
        title: '¿Aprobar planilla?',
        text: `La planilla ${payroll.code} quedará lista para pago.`,
        icon: 'question',
        confirmButtonText: 'Sí, aprobar',
    });

    if (!confirmed) {
        return;
    }

    router.patch(route('payrolls.approve', payroll.id), {}, { preserveScroll: true });
};

const rejectPayroll = async (payroll) => {
    const confirmed = await confirmAction({
        title: '¿Rechazar planilla?',
        text: `La planilla ${payroll.code} quedará observada y no podrá pagarse.`,
        icon: 'warning',
        confirmButtonText: 'Sí, rechazar',
    });

    if (!confirmed) {
        return;
    }

    router.patch(route('payrolls.reject', payroll.id), {}, { preserveScroll: true });
};

const observePayroll = async (payroll) => {
    const reason = await promptActionReason({
        title: 'Observar planilla',
        text: `Indica que debe corregirse en la planilla ${payroll.code}.`,
        placeholder: 'Describe la observacion...',
        confirmButtonText: 'Observar planilla',
    });

    if (!reason) {
        return;
    }

    router.patch(route('payrolls.observe', payroll.id), { reason }, { preserveScroll: true });
};

const rejectPayrollWithReason = async (payroll) => {
    const reason = await promptActionReason({
        title: 'Rechazar planilla',
        text: `Indica por que la planilla ${payroll.code} sera rechazada.`,
        placeholder: 'Describe el motivo del rechazo...',
        confirmButtonText: 'Rechazar planilla',
    });

    if (!reason) {
        return;
    }

    router.patch(route('payrolls.reject', payroll.id), { reason }, { preserveScroll: true });
};

const recalculatePayroll = async (payroll) => {
    const confirmed = await confirmAction({
        title: 'Recalcular planilla',
        text: `Se recalculara la planilla ${payroll.code} con las asistencias cerradas corregidas.`,
        icon: 'question',
        confirmButtonText: 'Recalcular',
    });

    if (!confirmed) {
        return;
    }

    router.patch(route('payrolls.recalculate', payroll.id), {}, { preserveScroll: true });
};

const payPayroll = async (payroll) => {
    const confirmed = await confirmAction({
        title: '¿Marcar como pagada?',
        text: `Se registrará el pago de la planilla ${payroll.code}.`,
        icon: 'success',
        confirmButtonText: 'Sí, marcar pagada',
    });

    if (!confirmed) {
        return;
    }

    router.patch(route('payrolls.pay', payroll.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Planillas" />

    <AuthenticatedLayout title="Planillas">
        <section class="space-y-6">
            <PageHeader
                title="Planillas"
                description="Genera, revisa y controla las planillas mensuales desde asistencias cerradas."
            >
                <template #icon>
                    <FileSpreadsheet class="h-7 w-7" />
                </template>
            </PageHeader>

            <SectionCard title="Generar planilla" description="Selecciona el periodo y calcula la planilla con asistencias mensuales cerradas.">
                <form class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_2fr_auto]" @submit.prevent="generatePayroll">
                    <div>
                        <InputLabel for="month" value="Mes" />
                        <select
                            id="month"
                            v-model="form.month"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                        >
                            <option v-for="month in monthOptions" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="fieldError('month')" />
                    </div>

                    <div>
                        <InputLabel for="year" value="Año" />
                        <select
                            id="year"
                            v-model="form.year"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                        >
                            <option v-for="year in yearOptions" :key="year.value" :value="year.value">
                                {{ year.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="fieldError('year')" />
                    </div>

                    <div>
                        <InputLabel for="payment_date" value="Fecha de pago" />
                        <input
                            id="payment_date"
                            v-model="form.payment_date"
                            type="date"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                        />
                        <InputError class="mt-2" :message="form.errors.payment_date" />
                    </div>

                    <div>
                        <InputLabel for="observations" value="Observacion" />
                        <input
                            id="observations"
                            v-model="form.observations"
                            type="text"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            placeholder="Planilla mensual de trabajadores activos."
                        />
                        <InputError class="mt-2" :message="form.errors.observations" />
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <Play class="h-4 w-4" />
                            {{ form.processing ? 'Generando...' : 'Generar' }}
                        </button>
                    </div>
                </form>
            </SectionCard>

            <FilterCard>
                <template #filters>
                    <SearchInput v-model="period" placeholder="Periodo YYYY-MM..." />

                    <select
                        v-model="statusId"
                        class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary"
                    >
                        <option value="">Todos los estados</option>
                        <option v-for="status in statuses" :key="status.id" :value="status.id">
                            {{ status.name }}
                        </option>
                    </select>

                    <PerPageFilter v-model="perPage" />
                </template>
            </FilterCard>

            <ListSummary
                title="Planillas generadas"
                description="Resumen de periodos calculados y su estado de revision."
                label="Total registros"
                :total="payrolls.total ?? payrolls.data.length"
                :icon="Database"
            >
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                        Neto filtrado: {{ money(totalNet) }}
                    </span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                        Trabajadores procesados: {{ totalEmployees }}
                    </span>
                </div>
            </ListSummary>

            <DataTable :columns="columns">
                <tr v-for="payroll in payrolls.data" :key="payroll.id" class="text-sm transition hover:bg-primary/5">
                    <td class="px-6 py-4">
                        <TableEntityCell
                            :icon="ReceiptText"
                            :title="payroll.code"
                            :subtitle="payroll.period"
                            :meta="payroll.payment_date ? `Pago: ${payroll.payment_date}` : 'Sin fecha de pago'"
                        />
                    </td>

                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-black" :class="statusClass(payroll.status?.code)">
                            {{ payroll.status?.name ?? 'Sin estado' }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-bold text-gray-700">
                        {{ payroll.employee_count }}
                    </td>

                    <td class="px-6 py-4 font-black text-primary-dark">
                        {{ money(payroll.total_net) }}
                    </td>

                    <td class="px-6 py-4">
                        <TableActions>
                            <TableActionButton :icon="Eye" title="Ver detalle" @click="selectedPayrollId = payroll.id" />
                            <TableActionButton
                                :icon="CheckCircle2"
                                title="Aprobar"
                                variant="success"
                                :disabled="!payroll.can_approve || !can('payrolls.approve')"
                                @click="approvePayroll(payroll)"
                            />
                            <TableActionButton
                                :icon="TriangleAlert"
                                title="Observar"
                                variant="warning"
                                :disabled="!payroll.can_observe || !can('payrolls.observe')"
                                @click="observePayroll(payroll)"
                            />
                            <TableActionButton
                                :icon="XCircle"
                                title="Rechazar"
                                variant="danger"
                                :disabled="!payroll.can_reject || !can('payrolls.reject')"
                                @click="rejectPayrollWithReason(payroll)"
                            />
                            <TableActionButton
                                :icon="RotateCcw"
                                title="Recalcular"
                                variant="neutral"
                                :disabled="!payroll.can_recalculate || !can('payrolls.recalculate')"
                                @click="recalculatePayroll(payroll)"
                            />
                            <TableActionButton
                                :icon="ClipboardCheck"
                                title="Marcar pagada"
                                variant="warning"
                                :disabled="!payroll.can_pay || !can('payrolls.pay')"
                                @click="payPayroll(payroll)"
                            />
                        </TableActions>
                    </td>
                </tr>

                <template v-if="payrolls.data.length === 0" #empty>
                    <td :colspan="columns.length">
                        <EmptyState
                            title="No se encontraron planillas"
                            description="Genera una planilla desde asistencias cerradas o modifica los filtros."
                        />
                    </td>
                </template>
            </DataTable>

            <div v-if="payrolls.links?.length > 3" class="rounded-3xl border border-slate-300 bg-white px-6 py-4 shadow-lg">
                <Pagination :links="payrolls.links" />
            </div>

            <SectionCard
                v-if="selectedPayroll"
                :title="`Detalle ${selectedPayroll.code}`"
                :description="`${selectedPayroll.period} · ${selectedPayroll.employee_count} trabajadores`"
            >
                <div
                    v-if="selectedPayroll.rejection_reason"
                    class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"
                >
                    Observacion registrada: {{ selectedPayroll.rejection_reason }}
                </div>

                <div class="mb-5 grid gap-3 md:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-gray-500">Ingresos</p>
                        <p class="mt-1 text-xl font-black text-gray-900">{{ money(selectedPayroll.total_income) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-gray-500">Descuentos</p>
                        <p class="mt-1 text-xl font-black text-danger">{{ money(selectedPayroll.total_discount) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-gray-500">Aporte empleador</p>
                        <p class="mt-1 text-xl font-black text-amber-700">{{ money(selectedPayroll.total_employer_contribution) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase text-gray-500">Neto</p>
                        <p class="mt-1 text-xl font-black text-primary-dark">{{ money(selectedPayroll.total_net) }}</p>
                    </div>
                </div>

                <DataTable :columns="detailColumns">
                    <tr v-for="detail in selectedPayroll.details" :key="detail.id" class="align-top text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4">
                            <TableEntityCell
                                :icon="Calculator"
                                :title="detail.employee_name"
                                :subtitle="`${detail.employee_code} · DNI ${detail.document_number}`"
                                :meta="detail.pension_system_name"
                            />
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <p><strong>{{ detail.worked_days }}</strong> dias trabajados</p>
                            <p>{{ detail.uncompensated_absence_days }} faltas sin compensar</p>
                            <p>{{ detail.overtime_hours }} horas extra</p>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-700">
                            {{ money(detail.total_income) }}
                        </td>
                        <td class="px-6 py-4 font-bold text-danger">
                            {{ money(detail.total_discount) }}
                        </td>
                        <td class="px-6 py-4 font-black text-primary-dark">
                            {{ money(detail.net_pay) }}
                        </td>
                    </tr>
                </DataTable>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
