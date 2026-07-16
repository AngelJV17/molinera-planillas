<script setup>
import { Head } from '@inertiajs/vue3';
import { UserCog } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout title="Perfil">
        <section class="space-y-6">
            <PageHeader
                title="Configuración de perfil"
                description="Administra tu información personal, contraseña y opciones de seguridad de tu cuenta."
            >
                <template #icon>
                    <UserCog class="h-7 w-7" />
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="space-y-6">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    <UpdatePasswordForm />
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
