<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, UserPlus } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import Form from './Partials/Form.vue';

defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    username: '',
    email: '',
    status: true,
    roles: [],
});

const submit = () => {
    form.post(route('users.store'), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Nuevo usuario" />

    <AuthenticatedLayout title="Nuevo usuario">
        <section class="space-y-6">
            <PageHeader title="Nuevo usuario"
                description="Registra una cuenta de acceso y asigna el rol que tendrá dentro del sistema MOLICENTE.">
                <template #icon>
                    <UserPlus class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link :href="route('users.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/5 hover:text-primary">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <Form :form="form" :roles="roles" submit-label="Registrar usuario" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
