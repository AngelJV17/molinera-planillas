<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Users } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import Form from './Partials/Form.vue';

defineProps({
    options: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    document_type_id: '',
    document_number: '',
    first_name: '',
    last_name: '',
    birth_date: '',
    gender_id: '',
    marital_status_id: '',
    phone: '',
    email: '',
    address: '',
    district_id: '',
    hire_date: '',
    termination_date: '',
    position_id: '',
    work_area_id: '',
    payroll_group_id: '',
    work_shift_id: '',
    employment_status_id: '',
    base_salary: 0,
    pension_system_id: '',
    cuspp: '',
    status: true,
    has_system_access: false,
    bank_accounts: [],
});

const submit = () => {
    form.post(route('workers.store'));
};
</script>

<template>

    <Head title="Nuevo trabajador" />

    <AuthenticatedLayout title="Nuevo trabajador">
        <section class="mx-auto max-w-5xl space-y-6">
            <PageHeader title="Registrar trabajador"
                description="Crea el expediente laboral base para asistencia, planillas y boletas.">
                <template #icon>
                    <Users class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link :href="route('workers.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <form @submit.prevent="submit">
                <Form :form="form" :options="options" />

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                        :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        Guardar
                    </button>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
