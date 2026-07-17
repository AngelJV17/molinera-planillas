<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Mail,
    ShieldCheck,
    KeyRound,
    HelpCircle,
} from 'lucide-vue-next';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

/**
 * Solicita el enlace de recuperación de contraseña.
 */
const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>

    <Head title="Recuperar contraseña" />

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
                            Recuperación de cuenta
                        </p>

                        <h2 class="mt-4 text-4xl font-black leading-tight">
                            Recupera tu acceso al sistema.
                        </h2>

                        <p class="mt-5 text-base leading-relaxed text-white/85">
                            Ingresa tu correo electrónico y enviaremos un enlace para
                            restablecer tu contraseña de forma segura.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <ShieldCheck class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            Proceso seguro para recuperar tus credenciales.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <KeyRound class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            Enlace de restablecimiento enviado por correo electrónico.
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
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <HelpCircle class="h-6 w-6" />
                            </div>

                            <h2 class="text-2xl font-black text-gray-900">
                                ¿Olvidaste tu contraseña?
                            </h2>

                            <p class="mt-2 text-sm leading-relaxed text-gray-500">
                                No hay problema. Ingresa tu correo electrónico y
                                recibirás un enlace para crear una nueva contraseña.
                            </p>
                        </div>

                        <div v-if="status"
                            class="mb-5 rounded-xl border border-primary/20 bg-primary/10 px-4 py-3 text-sm font-medium text-primary">
                            {{ status }}
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <!-- Email -->
                            <div>
                                <InputLabel for="email" value="Correo electrónico" required />

                                <div class="relative mt-2">
                                    <Mail class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                                    <TextInput id="email" v-model="form.email" type="email" required autofocus
                                        autocomplete="username" placeholder="usuario@empresa.com"
                                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                                </div>

                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <PrimaryButton
                                class="flex w-full justify-center rounded-xl bg-primary py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-primary-dark"
                                :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                                Enviar enlace de recuperación
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
