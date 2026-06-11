<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Leaf, LockKeyhole, Mail, ShieldCheck } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

/**
 * Envía las credenciales al backend.
 * Al finalizar, limpia únicamente el campo contraseña por seguridad.
 */
const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

    <Head title="Iniciar sesión" />

    <div class="flex min-h-screen bg-slate-100">
        <!-- Panel izquierdo -->
        <section
            class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-primary via-primary to-primary-dark text-white lg:flex">
            <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-24 right-10 h-80 w-80 rounded-full bg-secondary/20"></div>

            <div class="relative z-10 flex flex-col justify-between p-12">
                <div>
                    <div class="flex items-center gap-4">
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
                    </div>

                    <div class="mt-20 max-w-xl">
                        <p class="text-sm font-bold uppercase tracking-[0.3em] text-secondary">
                            Sistema Administrativo
                        </p>

                        <h2 class="mt-4 text-4xl font-black leading-tight">
                            Gestión de asistencia, planillas y boletas de pago.
                        </h2>

                        <p class="mt-5 text-base leading-relaxed text-white/85">
                            Plataforma diseñada para digitalizar la asistencia mensual,
                            automatizar la generación de planillas y facilitar la emisión
                            de boletas de pago del personal.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <ShieldCheck class="h-6 w-6 text-secondary" />
                        <p class="text-sm text-white/90">
                            Acceso seguro mediante usuarios autorizados.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <Leaf class="h-6 w-6 text-secondary" />
                        <p class="text-sm text-white/90">
                            Control administrativo adaptado a la operación del molino.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Panel derecho -->
        <section class="flex w-full items-center justify-center px-4 py-10 lg:w-1/2">
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

                    <div class="p-8">
                        <div class="mb-8">
                            <h2 class="text-2xl font-black text-gray-900">
                                Iniciar sesión
                            </h2>

                            <p class="mt-2 text-sm text-gray-500">
                                Ingresa tus credenciales para acceder al sistema.
                            </p>
                        </div>

                        <div v-if="status"
                            class="mb-5 rounded-xl border border-primary/20 bg-primary/10 px-4 py-3 text-sm font-medium text-primary">
                            {{ status }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <!-- Email -->
                            <div>
                                <InputLabel for="email" value="Correo electrónico" />

                                <div class="relative mt-2">
                                    <Mail class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                                    <TextInput id="email" type="email"
                                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                        v-model="form.email" required autofocus autocomplete="username"
                                        placeholder="usuario@empresa.com" />
                                </div>

                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <!-- Password -->
                            <div>
                                <InputLabel for="password" value="Contraseña" />

                                <div class="relative mt-2">
                                    <LockKeyhole class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                                    <TextInput id="password" type="password"
                                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                        v-model="form.password" required autocomplete="current-password"
                                        placeholder="••••••••" />
                                </div>

                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <!-- Remember + forgot -->
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <label class="flex items-center">
                                    <Checkbox name="remember" v-model:checked="form.remember" />

                                    <span class="ms-2 text-sm text-gray-600">
                                        Recordarme
                                    </span>
                                </label>

                                <Link v-if="canResetPassword" :href="route('password.request')"
                                    class="text-sm font-semibold text-primary transition hover:text-primary-dark">
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>

                            <!-- Button -->
                            <PrimaryButton
                                class="flex w-full justify-center rounded-xl bg-primary py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-primary-dark"
                                :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                                Acceder al sistema
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
