<script setup>
import { ref } from 'vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

import { Head, useForm } from '@inertiajs/vue3';

import {
    Eye,
    EyeOff,
    KeyRound,
    LockKeyhole,
    ShieldAlert,
    ShieldCheck,
    UserCheck,
} from 'lucide-vue-next';

/**
 * Formulario para cambiar la contraseña temporal.
 *
 * Este formulario se muestra cuando el usuario inicia sesión
 * con una contraseña temporal generada por el administrador.
 */
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

/**
 * Controla la visibilidad de cada campo de contraseña.
 *
 * Cada campo se maneja por separado para que el usuario pueda revisar
 * únicamente la contraseña que necesita ver.
 */
const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

/**
 * Envía la nueva contraseña al backend.
 *
 * Si la contraseña temporal es correcta, el sistema:
 * - Actualiza la contraseña.
 * - Cambia must_change_password a false.
 * - Permite el acceso normal al sistema.
 */
const submit = () => {
    form.put(route('password.temporary.update'), {
        preserveScroll: true,

        onFinish: () => {
            form.reset(
                'current_password',
                'password',
                'password_confirmation',
            );

            // Por seguridad, al finalizar el proceso ocultamos nuevamente los campos.
            showCurrentPassword.value = false;
            showPassword.value = false;
            showPasswordConfirmation.value = false;
        },
    });
};
</script>

<template>

    <Head title="Cambiar contraseña temporal" />

    <div class="flex min-h-screen overflow-hidden bg-slate-100">
        <!-- Panel izquierdo corporativo -->
        <section
            class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-primary via-primary to-primary-dark text-white lg:flex">
            <!-- Figuras decorativas -->
            <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-24 right-10 h-80 w-80 rounded-full bg-secondary/20"></div>
            <div class="absolute right-32 top-24 h-32 w-32 rounded-full bg-white/5"></div>

            <div class="relative z-10 flex flex-col justify-between p-10">
                <div>
                    <!-- Identidad visual -->
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

                    <!-- Mensaje principal -->
                    <div class="mt-14 max-w-xl">
                        <p class="text-sm font-bold uppercase tracking-[0.3em] text-secondary">
                            Primer acceso seguro
                        </p>

                        <h2 class="mt-4 text-4xl font-black leading-tight">
                            Actualiza tu contraseña temporal.
                        </h2>

                        <p class="mt-5 text-base leading-relaxed text-white/85">
                            Para proteger tu cuenta, debes registrar una contraseña
                            personal antes de continuar usando la plataforma.
                        </p>
                    </div>
                </div>

                <!-- Mensajes de seguridad -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <ShieldCheck class="h-6 w-6 shrink-0 text-secondary" />

                        <p class="text-sm text-white/90">
                            La contraseña temporal solo sirve para el primer ingreso.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <UserCheck class="h-6 w-6 shrink-0 text-secondary" />

                        <p class="text-sm text-white/90">
                            Después del cambio, podrás acceder normalmente al sistema.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <KeyRound class="h-6 w-6 shrink-0 text-secondary" />

                        <p class="text-sm text-white/90">
                            Usa una contraseña personal, segura y fácil de recordar.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Panel derecho del formulario -->
        <section class="flex w-full items-center justify-center px-4 py-6 lg:w-1/2">
            <div class="w-full max-w-md">
                <!-- Logo visible en móvil -->
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

                <!-- Tarjeta principal -->
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                    <!-- Línea superior corporativa -->
                    <div class="h-1.5 bg-primary"></div>

                    <div class="p-8">
                        <!--
                            Encabezado sin ícono.
                            Se mantiene igual al estilo visual usado en Login y Register.
                        -->
                        <div class="mb-8">
                            <h2 class="text-2xl font-black text-gray-900">
                                Cambiar contraseña
                            </h2>

                            <p class="mt-2 text-sm leading-relaxed text-gray-500">
                                Estás usando una contraseña temporal. Registra una nueva
                                contraseña personal para continuar en el sistema.
                            </p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <!-- Contraseña temporal -->
                            <div>
                                <InputLabel for="current_password" value="Contraseña temporal" required />

                                <div class="relative mt-2">
                                    <KeyRound class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                                    <TextInput id="current_password" v-model="form.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        autocomplete="current-password" required autofocus
                                        class="block w-full rounded-xl border-slate-300 pl-11 pr-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                        placeholder="Ingresa la contraseña temporal" />

                                    <!-- Botón para mostrar u ocultar contraseña temporal -->
                                    <button type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-400 transition hover:bg-slate-100 hover:text-primary"
                                        :title="showCurrentPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        :aria-label="showCurrentPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        @click="showCurrentPassword = !showCurrentPassword">
                                        <component :is="showCurrentPassword ? EyeOff : Eye" class="h-5 w-5" />
                                    </button>
                                </div>

                                <InputError class="mt-2" :message="form.errors.current_password" />
                            </div>

                            <!-- Nueva contraseña -->
                            <div>
                                <InputLabel for="password" value="Nueva contraseña" required />

                                <div class="relative mt-2">
                                    <LockKeyhole
                                        class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                                    <TextInput id="password" v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required
                                        class="block w-full rounded-xl border-slate-300 pl-11 pr-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                        placeholder="Mínimo 8 caracteres" />

                                    <!-- Botón para mostrar u ocultar nueva contraseña -->
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

                            <!-- Confirmar nueva contraseña -->
                            <div>
                                <InputLabel for="password_confirmation" value="Confirmar nueva contraseña" required />

                                <div class="relative mt-2">
                                    <LockKeyhole
                                        class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                                    <TextInput id="password_confirmation" v-model="form.password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        autocomplete="new-password" required
                                        class="block w-full rounded-xl border-slate-300 pl-11 pr-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                        placeholder="Repite la nueva contraseña" />

                                    <!-- Botón para mostrar u ocultar confirmación de contraseña -->
                                    <button type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-gray-400 transition hover:bg-slate-100 hover:text-primary"
                                        :title="showPasswordConfirmation ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        :aria-label="showPasswordConfirmation ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation">
                                        <component :is="showPasswordConfirmation ? EyeOff : Eye" class="h-5 w-5" />
                                    </button>
                                </div>

                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>

                            <!-- Aviso de seguridad -->
                            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
                                <div class="flex items-start gap-3">
                                    <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0 text-primary" />

                                    <p class="text-xs font-semibold leading-relaxed text-gray-600">
                                        La nueva contraseña debe tener al menos 8 caracteres,
                                        incluir letras y números, y ser diferente a la contraseña temporal.
                                    </p>
                                </div>
                            </div>

                            <!-- Botón de envío sin ícono, igual que Login y Register -->
                            <PrimaryButton
                                class="flex w-full justify-center rounded-xl bg-primary py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-primary-dark"
                                :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                                {{ form.processing ? 'Guardando...' : 'Cambiar contraseña' }}
                            </PrimaryButton>
                        </form>
                    </div>
                </div>

                <!-- Pie de página -->
                <p class="mt-6 text-center text-xs text-gray-500">
                    © 2026 Industria Molinera San Vicente SRL
                </p>
            </div>
        </section>
    </div>
</template>
