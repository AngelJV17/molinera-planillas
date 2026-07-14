<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Clock3, Save } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

const props = defineProps({
    workShift: {
        type: Object,
        required: true,
    },
});

const timeValue = (value) => value?.slice(0, 5) ?? '';
const dayLabels = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miercoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sabado',
    7: 'Domingo',
};

const dailyRules = () => {
    const rules = props.workShift.rules ?? [];

    return Array.from({ length: 7 }, (_, index) => {
        const day = index + 1;
        const rule = rules.find((item) => Number(item.day_of_week) === day) ?? {};

        return {
            day_of_week: day,
            label: dayLabels[day],
            is_working_day: rule.is_working_day ?? day !== 7,
            start_time: timeValue(rule.start_time ?? props.workShift.start_time),
            break_start_time: timeValue(rule.break_start_time ?? props.workShift.break_start_time),
            break_end_time: timeValue(rule.break_end_time ?? props.workShift.break_end_time),
            end_time: timeValue(rule.end_time ?? props.workShift.end_time),
            tolerance_minutes: rule.tolerance_minutes ?? props.workShift.tolerance_minutes,
            expected_hours: rule.expected_hours ?? props.workShift.daily_hours,
            crosses_midnight: rule.crosses_midnight ?? props.workShift.crosses_midnight,
            counts_as_full_day: rule.counts_as_full_day ?? true,
            overtime_pay_enabled: rule.overtime_pay_enabled ?? true,
            overtime_after_hours: rule.overtime_after_hours ?? '',
        };
    });
};

const form = useForm({
    name: props.workShift.name,
    description: props.workShift.description ?? '',
    start_time: timeValue(props.workShift.start_time),
    break_start_time: timeValue(props.workShift.break_start_time),
    break_end_time: timeValue(props.workShift.break_end_time),
    end_time: timeValue(props.workShift.end_time),
    tolerance_minutes: props.workShift.tolerance_minutes,
    daily_hours: props.workShift.daily_hours,
    uses_daily_rules: props.workShift.uses_daily_rules ?? false,
    crosses_midnight: props.workShift.crosses_midnight,
    rotation_enabled: props.workShift.rotation_enabled ?? false,
    rotation_work_days: props.workShift.rotation_work_days ?? 6,
    rotation_rest_days: props.workShift.rotation_rest_days ?? 1,
    rotation_start_date: props.workShift.rotation_start_date?.slice(0, 10) ?? '',
    status: props.workShift.status,
    daily_rules: dailyRules(),
});

const submit = () => {
    form.put(route('work-shifts.update', props.workShift.id));
};
</script>

<template>
    <Head title="Editar turno" />

    <AuthenticatedLayout title="Editar turno">
        <section class="mx-auto max-w-3xl space-y-6">
            <PageHeader
                title="Editar turno"
                description="Actualiza el horario laboral y sus reglas de control."
            >
                <template #icon>
                    <Clock3 class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link
                        :href="route('organizational-structure.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <SectionCard title="Datos del turno" description="Revisa los campos antes de guardar los cambios.">
                <form @submit.prevent="submit">
                    <Form :form="form" />

                    <div class="mt-6 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <Save class="h-4 w-4" />
                            Actualizar
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
