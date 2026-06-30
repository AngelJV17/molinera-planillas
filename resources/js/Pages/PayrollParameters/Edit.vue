<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, SlidersHorizontal } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

const props = defineProps({
    parameter: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    code: props.parameter.code ?? '',
    name: props.parameter.name ?? '',
    value: props.parameter.value ?? '',
    effective_from: props.parameter.effective_from ?? '',
    status: Boolean(props.parameter.status),
    description: props.parameter.description ?? '',
});

const submit = () => {
    form.put(route('payroll-parameters.update', props.parameter.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar parametro de planilla" />

    <AuthenticatedLayout title="Parametros de planilla">
        <section class="space-y-6">
            <PageHeader
                title="Editar parametro"
                description="Actualiza el valor y datos visibles del parametro seleccionado."
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

            <SectionCard title="Datos del parametro" description="Revisa el codigo tecnico antes de modificarlo.">
                <form class="space-y-6" @submit.prevent="submit">
                    <Form :form="form" />

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <Save class="h-4 w-4" />
                            Actualizar parametro
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
