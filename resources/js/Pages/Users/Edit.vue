<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, UserCog } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import Form from './Partials/Form.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },

    roles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.user.name ?? '',
    username: props.user.username ?? '',
    email: props.user.email ?? '',
    status: props.user.status ?? true,
    roles: props.user.roles ?? [],
});

const submit = () => {
    form.put(route('users.update', props.user.id), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Editar usuario" />

    <AuthenticatedLayout title="Editar usuario">
        <section class="space-y-6">
            <PageHeader title="Editar usuario"
                description="Actualiza los datos de acceso y el rol asignado al usuario seleccionado.">
                <template #icon>
                    <UserCog class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link :href="route('users.index')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:border-primary hover:bg-primary/5 hover:text-primary">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <Form :form="form" :roles="roles" :is-edit="true" submit-label="Actualizar usuario" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
