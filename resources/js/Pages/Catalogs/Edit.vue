<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ListChecks, Save } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

const props = defineProps({
    catalog: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    type: props.catalog.type,
    code: props.catalog.code,
    name: props.catalog.name,
    description: props.catalog.description,
    status: props.catalog.status,
});

const submit = () => {
    form.put(route('catalogs.update', props.catalog.id));
};
</script>

<template>
    <Head title="Editar catálogo" />

    <AuthenticatedLayout title="Editar catálogo">
        <section class="mx-auto max-w-3xl space-y-6">
            <PageHeader
                title="Editar catálogo"
                description="Actualiza la información de la configuración seleccionada."
            >
                <template #icon>
                    <ListChecks class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link
                        :href="route('catalogs.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <SectionCard
                title="Datos del catálogo"
                description="Revisa los campos antes de guardar los cambios."
            >
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
