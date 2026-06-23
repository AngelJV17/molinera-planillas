<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Save } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

const form = useForm({
    name: '',
    code: '',
    status: true,
});

const submit = () => {
    form.post(route('banks.store'));
};
</script>

<template>
    <Head title="Nuevo banco" />

    <AuthenticatedLayout title="Nuevo banco">
        <section class="mx-auto max-w-3xl space-y-6">
            <PageHeader
                title="Registrar banco"
                description="Crea una entidad financiera para asociarla a las cuentas bancarias de los trabajadores."
            >
                <template #icon>
                    <Building2 class="h-7 w-7" />
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

            <SectionCard
                title="Datos del banco"
                description="Completa la información principal del registro."
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
                            Guardar
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
