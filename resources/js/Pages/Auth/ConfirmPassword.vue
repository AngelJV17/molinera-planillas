<script setup>
import { ref } from 'vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

import { Head, useForm } from '@inertiajs/vue3';

import {
    AlertTriangle,
    Eye,
    EyeOff,
    KeyRound,
    LockKeyhole,
    ShieldCheck,
} from 'lucide-vue-next';

const form = useForm({
    password: '',
});

/**
 * Controla si la contraseña actual se muestra como texto
 * o se mantiene oculta.
 */
const showPassword = ref(false);

/**
 * Confirma la contraseña actual antes de permitir
 * acceder a una acción sensible del sistema.
 */
const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>

    <Head title="Confirmar contraseña" />

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
                            Área protegida
                        </p>

                        <h2 class="mt-4 text-4xl font-black leading-tight">
                            Confirmación de identidad.
                        </h2>

                        <p class="mt-5 text-base leading-relaxed text-white/85">
                            Antes de continuar con una operación importante,
                            necesitamos verificar tu identidad mediante tu contraseña.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <ShieldCheck class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            Protección adicional para acciones sensibles.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <KeyRound class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            Validación segura mediante tu contraseña actual.
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
                                <AlertTriangle class="h-6 w-6" />
                            </div>

                            <h2 class="text-2xl font-black text-gray-900">
                                Confirmar contraseña
                            </h2>

                            <p class="mt-2 text-sm leading-relaxed text-gray-500">
                                Esta es una zona protegida del sistema.
                                Ingresa tu contraseña para continuar.
                            </p>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <InputLabel for="password" value="Contraseña actual" required />

                                <div class="relative mt-2">
                                    <LockKeyhole
                                        class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                                    <TextInput id="password" v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'" required autofocus
                                        autocomplete="current-password" placeholder="••••••••"
                                        class="block w-full rounded-xl border-slate-300 px-11 text-sm shadow-sm focus:border-primary focus:ring-primary" />

                                    <!-- Botón para mostrar u ocultar la contraseña actual -->
                                    <button type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-400 transition hover:bg-slate-100 hover:text-primary"
                                        :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        @click="showPassword = !showPassword">
                                        <component :is="showPassword ? EyeOff : Eye" class="h-5 w-5" />
                                    </button>
                                </div>

                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <PrimaryButton
                                class="flex w-full justify-center rounded-xl bg-primary py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-primary-dark"
                                :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                                Confirmar acceso
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
