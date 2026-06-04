<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { UserCog } from 'lucide-vue-next';

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
            <div class="overflow-hidden rounded-3xl border border-primary/20 bg-white shadow-lg">
                <div class="h-1.5 bg-primary"></div>

                <div class="flex gap-4 p-6">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                        <UserCog class="h-7 w-7" />
                    </div>

                    <div>
                        <h1 class="text-2xl font-black text-gray-900">
                            Configuración de perfil
                        </h1>

                        <p class="mt-1 max-w-3xl text-sm leading-relaxed text-gray-600">
                            Administra tu información personal, contraseña y opciones de seguridad de tu cuenta.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="space-y-6 xl:col-span-2">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />

                    <UpdatePasswordForm />
                </div>

                <DeleteUserForm />
            </div>
        </section>
    </AuthenticatedLayout>
</template>
