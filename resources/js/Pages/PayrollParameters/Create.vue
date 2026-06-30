<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, SlidersHorizontal } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

const form = useForm({
    code: '',
    name: '',
    value: '',
    effective_from: '',
    status: true,
    description: '',
});

const submit = () => {
    form.post(route('payroll-parameters.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo parametro de planilla" />

    <AuthenticatedLayout title="Parametros de planilla">
        <section class="space-y-6">
            <PageHeader
                title="Nuevo parametro"
                description="Registra un valor usado por el calculo de planillas."
            >
                <template #icon>
                    <SlidersHorizontal class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link
                        :href="route('payroll-parameters.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-slate-50"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <SectionCard title="Datos del parametro" description="Completa el codigo, valor y vigencia del parametro.">
                <form class="space-y-6" @submit.prevent="submit">
                    <Form :form="form" />

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <Save class="h-4 w-4" />
                            Registrar parametro
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
