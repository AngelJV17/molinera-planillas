<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import Form from './Partials/Form.vue';

const props = defineProps({
    role: {
        type: Object,
        required: true,
    },

    permissions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.role.name ?? '',
    permissions: props.role.permissions ?? [],
});

const submit = () => {
    form.put(route('roles.update', props.role.id), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Editar rol" />

    <AuthenticatedLayout title="Editar rol">
        <section class="space-y-6">
            <PageHeader title="Editar rol"
                description="Actualiza el perfil de acceso y administra los permisos asignados al rol seleccionado.">
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

            <Form :form="form" :permissions="permissions" :is-edit="true" :is-protected="role.is_protected"
                submit-label="Actualizar rol" @submit="submit" />
        </section>
    </AuthenticatedLayout>
</template>
