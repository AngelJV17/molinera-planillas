<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, LockKeyhole, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

/**
 * Actualiza la contraseña del usuario autenticado.
 * Si hay errores, se limpian los campos sensibles correspondientes.
 */
const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="overflow-hidden rounded-3xl border border-slate-300 bg-white shadow-lg">
        <div class="border-b border-slate-200 bg-slate-100 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-primary">
                    <ShieldCheck class="h-5 w-5" />
                </div>

                <div>
                    <h2 class="text-sm font-black uppercase tracking-wide text-gray-800">
                        Seguridad de la cuenta
                    </h2>

                    <p class="text-xs text-gray-500">
                        Actualiza tu contraseña de acceso al sistema.
                    </p>
                </div>
            </div>
        </div>

        <form class="space-y-5 p-6" @submit.prevent="updatePassword">
            <div>
                <InputLabel for="current_password" value="Contraseña actual" required />

                <div class="relative mt-2">
                    <LockKeyhole class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                    <TextInput id="current_password" ref="currentPasswordInput" v-model="form.current_password"
                        type="password"
                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                        autocomplete="current-password" />
                </div>

                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <InputLabel for="password" value="Nueva contraseña" required />

                    <div class="relative mt-2">
                        <LockKeyhole class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                        <TextInput id="password" ref="passwordInput" v-model="form.password" type="password"
                            class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            autocomplete="new-password" />
                    </div>

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirmar contraseña" required />

                    <div class="relative mt-2">
                        <LockKeyhole class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                        <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password"
                            class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            autocomplete="new-password" />
                    </div>

                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing"
                    class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark">
                    Actualizar contraseña
                </PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful"
                        class="flex items-center gap-2 text-sm font-semibold text-primary">
                        <CheckCircle2 class="h-4 w-4" />
                        Contraseña actualizada.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
