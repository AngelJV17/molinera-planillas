<script setup>
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    MailCheck,
    ShieldCheck,
    Send,
    LogOut,
    CheckCircle2,
} from 'lucide-vue-next';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

/**
 * Solicita el reenvío del correo de verificación.
 */
const submit = () => {
    form.post(route('verification.send'));
};

/**
 * Laravel devuelve este estado cuando el enlace fue reenviado correctamente.
 */
const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>

    <Head title="Verificación de correo" />

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
                            Verificación de cuenta
                        </p>

                        <h2 class="mt-4 text-4xl font-black leading-tight">
                            Confirma tu correo electrónico.
                        </h2>

                        <p class="mt-5 text-base leading-relaxed text-white/85">
                            Para proteger el acceso al sistema, verifica tu correo
                            electrónico antes de continuar.
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <ShieldCheck class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            Validación de identidad para usuarios autorizados.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur">
                        <MailCheck class="h-6 w-6 text-secondary" />

                        <p class="text-sm text-white/90">
                            El enlace de verificación se envía al correo registrado.
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
                                <MailCheck class="h-6 w-6" />
                            </div>

                            <h2 class="text-2xl font-black text-gray-900">
                                Verifica tu correo
                            </h2>

                            <p class="mt-2 text-sm leading-relaxed text-gray-500">
                                Antes de acceder al sistema, revisa tu correo y haz clic
                                en el enlace de verificación enviado.
                            </p>
                        </div>

                        <div v-if="verificationLinkSent"
                            class="mb-5 flex gap-3 rounded-xl border border-primary/20 bg-primary/10 px-4 py-3 text-sm font-medium text-primary">
                            <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0" />

                            <span>
                                Se envió un nuevo enlace de verificación al correo registrado.
                            </span>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <PrimaryButton
                                class="flex w-full justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:bg-primary-dark"
                                :class="{ 'opacity-60': form.processing }" :disabled="form.processing">
                                <Send class="h-4 w-4" />
                                Reenviar correo de verificación
                            </PrimaryButton>

                            <Link :href="route('logout')" method="post" as="button"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-slate-50">
                                <LogOut class="h-4 w-4" />
                                Cerrar sesión
                            </Link>
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
