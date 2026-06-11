<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    LockKeyhole,
    Mail,
    ShieldCheck,
    KeyRound,
} from 'lucide-vue-next';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

/**
 * Restablece la contraseña del usuario.
 */
const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset(
            'password',
            'password_confirmation'
        ),
    });
};
</script>

<template>

    <Head title="Restablecer contraseña" />

    <div class="flex min-h-screen overflow-hidden bg-slate-100">
        <!-- Panel izquierdo -->
        <section
            class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-primary via-primary to-primary-dark text-white lg:flex">
            <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-24 right-10 h-80 w-80 rounded-full bg-secondary/20"></div>

            <div class="relative z-10 flex flex-col justify-between p-10">
                <div>
                    <div class="flex items-center gap-4">
                        <img src="/images/molicente-icon.png" alt="MOLICENTE"
                            class="h-24 w-auto rounded-xl bg-white p-3 shadow-xl" />
                        <div>
                            <h1 class="font-rajdhani text-3xl font-black text-white">
                                MOLICENTE
                            </h1>

                            <p class="text-sm text-white/80">
                                Industria Molinera San Vicente SRL
                            </p>
                        </div>
                    </div>

                    <div class="mt-14 max-w-xl">
                        <p class="text-sm font-bold uppercase tracking-[0.3em] text-secondary">
                            Recuperación de acceso
                        </p>

                        <h2 class="mt-4 text-4xl font-black leading-tight">
                            Restablece tu contraseña de forma segura.
                        </h2>

                        <p class="mt-5 text-base leading-relaxed text-white/85">
                            Ingresa una nueva contraseña para recuperar el acceso
                            a la plataforma de gestión administrativa.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <ShieldCheck class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            Protección de credenciales y acceso seguro.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <KeyRound class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            Recuperación rápida mediante correo electrónico.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Panel derecho -->
        <section class="flex w-full items-center justify-center overflow-y-auto px-4 py-6 lg:w-1/2">
            <div class="w-full max-w-md">
                <!-- Logo móvil -->
                <div class="mb-8 flex justify-center lg:hidden">
                    <div class="text-center">
                        <div
                            class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary text-white shadow-lg">
                            <img src="/images/molicente-icon.png" alt="MOLICENTE"
                                class="w-auto rounded-xl bg-white p-3 shadow-xl" />
                        </div>

                        <h1 class="font-rajdhani mt-3 text-2xl font-black text-primary">
                            MOLICENTE
                        </h1>

                        <p class="text-sm text-gray-500">
                            Industria Molinera San Vicente SRL
                        </p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                    <div class="h-1.5 bg-primary"></div>

                    <div class="p-6">
                        <div class="mb-8">
                            <h2 class="text-2xl font-black text-gray-900">
                                Restablecer contraseña
                            </h2>

                            <p class="mt-2 text-sm text-gray-500">
                                Define una nueva contraseña para tu cuenta.
                            </p>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <!-- Email -->
                            <div>
                                <InputLabel for="email" value="Correo electrónico" />

                                <div class="relative mt-2">
                                    <Mail class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                                    <TextInput id="email" v-model="form.email" type="email" autocomplete="username"
                                        required
                                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                </div>

                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <!-- Password -->
                            <div>
                                <InputLabel for="password" value="Nueva contraseña" />

                                <div class="relative mt-2">
                                    <LockKeyhole class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                                    <TextInput id="password" v-model="form.password" type="password"
                                        autocomplete="new-password" required
                                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                </div>

                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <!-- Confirmación -->
                            <div>
                                <InputLabel for="password_confirmation" value="Confirmar contraseña" />

                                <div class="relative mt-2">
                                    <LockKeyhole class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                                    <TextInput id="password_confirmation" v-model="form.password_confirmation"
                                        type="password" autocomplete="new-password" required
                                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                </div>

                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>

                            <PrimaryButton
                                class="flex w-full justify-center rounded-xl bg-primary py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-primary-dark"
                                :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                                Restablecer contraseña
                            </PrimaryButton>
                        </form>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-gray-500">
                    © 2026 Industria Molinera San Vicente SRL
                </p>
            </div>
        </section>
    </div>
</template>
