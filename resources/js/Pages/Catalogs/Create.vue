<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ListChecks, Save } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
    },
});

/**
 * Categorías permitidas para mostrar nombres entendibles al usuario.
 */
const categoryLabels = {
    DOCUMENT_TYPE: 'Tipo de documento',
    GENDER: 'Género',
    MARITAL_STATUS: 'Estado civil',
    WORK_AREA: 'Área de trabajo',
    POSITION: 'Cargo',
    WORKER_STATUS: 'Estado laboral',
    PENSION_SYSTEM: 'Sistema pensionario',
    ACCOUNT_TYPE: 'Tipo de cuenta',
};

const categoryLabel = categoryLabels[props.type] ?? 'Configuración';

const form = useForm({
    type: props.type,
    code: '',
    name: '',
    description: '',
    status: true,
});

const submit = () => {
    form.post(route('catalogs.store'));
};
</script>

<template>

    <Head :title="`Nuevo ${categoryLabel}`" />

    <AuthenticatedLayout :title="`Nuevo ${categoryLabel}`">
        <section class="mx-auto max-w-3xl space-y-6">
            <PageHeader :title="`Registrar ${categoryLabel}`"
                description="Crea una nueva opción reutilizable para los módulos del sistema.">
                <template #icon>
                    <ListChecks class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link :href="route('catalogs.index', { type: form.type })"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <SectionCard :title="`Datos de ${categoryLabel}`"
                description="Completa la información principal del registro.">
                <form @submit.prevent="submit">
                    <Form :form="form" :category-label="categoryLabel" />

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing">
                            <Save class="h-4 w-4" />
                            Guardar
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
