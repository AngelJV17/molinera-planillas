<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Clock3, Save } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

const defaultDailyRules = () => [
    { day_of_week: 1, label: 'Lunes', is_working_day: true, start_time: '08:00', break_start_time: '12:00', break_end_time: '13:00', end_time: '17:00', tolerance_minutes: 10, expected_hours: 8, crosses_midnight: false, counts_as_full_day: true, overtime_pay_enabled: true, overtime_after_hours: '' },
    { day_of_week: 2, label: 'Martes', is_working_day: true, start_time: '08:00', break_start_time: '12:00', break_end_time: '13:00', end_time: '17:00', tolerance_minutes: 10, expected_hours: 8, crosses_midnight: false, counts_as_full_day: true, overtime_pay_enabled: true, overtime_after_hours: '' },
    { day_of_week: 3, label: 'Miercoles', is_working_day: true, start_time: '08:00', break_start_time: '12:00', break_end_time: '13:00', end_time: '17:00', tolerance_minutes: 10, expected_hours: 8, crosses_midnight: false, counts_as_full_day: true, overtime_pay_enabled: true, overtime_after_hours: '' },
    { day_of_week: 4, label: 'Jueves', is_working_day: true, start_time: '08:00', break_start_time: '12:00', break_end_time: '13:00', end_time: '17:00', tolerance_minutes: 10, expected_hours: 8, crosses_midnight: false, counts_as_full_day: true, overtime_pay_enabled: true, overtime_after_hours: '' },
    { day_of_week: 5, label: 'Viernes', is_working_day: true, start_time: '08:00', break_start_time: '12:00', break_end_time: '13:00', end_time: '17:00', tolerance_minutes: 10, expected_hours: 8, crosses_midnight: false, counts_as_full_day: true, overtime_pay_enabled: true, overtime_after_hours: '' },
    { day_of_week: 6, label: 'Sabado', is_working_day: true, start_time: '08:00', break_start_time: '', break_end_time: '', end_time: '12:00', tolerance_minutes: 10, expected_hours: 4, crosses_midnight: false, counts_as_full_day: true, overtime_pay_enabled: false, overtime_after_hours: '' },
    { day_of_week: 7, label: 'Domingo', is_working_day: false, start_time: '08:00', break_start_time: '', break_end_time: '', end_time: '12:00', tolerance_minutes: 10, expected_hours: 0, crosses_midnight: false, counts_as_full_day: false, overtime_pay_enabled: false, overtime_after_hours: '' },
];

const form = useForm({
    name: '',
    description: '',
    start_time: '08:00',
    break_start_time: '13:00',
    break_end_time: '15:00',
    end_time: '18:00',
    tolerance_minutes: 10,
    daily_hours: 8,
    uses_daily_rules: true,
    crosses_midnight: false,
    rotation_enabled: false,
    rotation_work_days: 6,
    rotation_rest_days: 1,
    rotation_start_date: '',
    status: true,
    daily_rules: defaultDailyRules(),
});

const submit = () => {
    form.post(route('work-shifts.store'));
};
</script>

<template>
    <Head title="Nuevo turno" />

    <AuthenticatedLayout title="Nuevo turno">
        <section class="mx-auto max-w-3xl space-y-6">
            <PageHeader
                title="Registrar turno"
                description="Crea un horario laboral para asignarlo posteriormente a los trabajadores."
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

            <SectionCard title="Datos del turno" description="Define el nombre, horario y reglas básicas del turno.">
                <form @submit.prevent="submit">
                    <Form :form="form" />

                    <div class="mt-6 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <Save class="h-4 w-4" />
                            Guardar
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
