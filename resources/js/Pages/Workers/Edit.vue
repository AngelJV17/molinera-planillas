<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Users } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import Form from './Partials/Form.vue';

const props = defineProps({
    worker: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        required: true,
    },
});

const dateValue = (value) => value?.slice(0, 10) ?? '';

const form = useForm({
    employee_code: props.worker.employee_code,
    document_type_id: props.worker.document_type_id,
    document_number: props.worker.document_number,
    first_name: props.worker.first_name,
    last_name: props.worker.last_name,
    birth_date: dateValue(props.worker.birth_date),
    gender_id: props.worker.gender_id ?? '',
    marital_status_id: props.worker.marital_status_id ?? '',
    phone: props.worker.phone ?? '',
    email: props.worker.email ?? '',
    address: props.worker.address ?? '',
    district_id: props.worker.district_id ?? '',
    hire_date: dateValue(props.worker.hire_date),
    termination_date: dateValue(props.worker.termination_date),
    position_id: props.worker.position_id ?? '',
    work_area_id: props.worker.work_area_id ?? '',
    work_shift_id: props.worker.work_shift_id ?? '',
    employment_status_id: props.worker.employment_status_id ?? '',
    base_salary: props.worker.base_salary,
    pension_system_id: props.worker.pension_system_id ?? '',
    cuspp: props.worker.cuspp ?? '',
    status: props.worker.status,
});

const submit = () => {
    form.put(route('workers.update', props.worker.id));
};
</script>

<template>
    <Head title="Editar trabajador" />

    <AuthenticatedLayout title="Editar trabajador">
        <section class="mx-auto max-w-5xl space-y-6">
            <PageHeader
                title="Editar trabajador"
                description="Actualiza la información personal y laboral del trabajador."
            >
                <template #icon>
                    <Users class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link
                        :href="route('workers.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <form @submit.prevent="submit">
                <Form :form="form" :options="options" :initial-district="worker.district" />

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
        </section>
    </AuthenticatedLayout>
</template>
