<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import Form from './Partials/Form.vue';

defineProps({
    permissions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    permissions: [],
});

const submit = () => {
    form.post(route('roles.store'), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Nuevo rol" />

    <AuthenticatedLayout title="Nuevo rol">
        <section class="space-y-6">
            <PageHeader title="Nuevo rol"
                description="Registra un perfil de acceso y asigna los permisos que tendrá dentro del sistema MOLICENTE.">
                <template #icon>
                    <ShieldCheck class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link :href="route('roles.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/5 hover:text-primary">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <Form :form="form" :permissions="permissions" submit-label="Registrar rol" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
