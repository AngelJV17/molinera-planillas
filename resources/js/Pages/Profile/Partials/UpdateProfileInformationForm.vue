<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Mail, UserRound } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section class="overflow-hidden rounded-3xl border border-slate-300 bg-white shadow-lg">
        <div class="border-b border-slate-200 bg-slate-100 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-primary">
                    <UserRound class="h-5 w-5" />
                </div>

                <div>
                    <h2 class="text-sm font-black uppercase tracking-wide text-gray-800">
                        Información del perfil
                    </h2>

                    <p class="text-xs text-gray-500">
                        Actualiza tus datos personales y correo electrónico.
                    </p>
                </div>
            </div>
        </div>

        <form class="space-y-5 p-6" @submit.prevent="form.patch(route('profile.update'))">
            <div>
                <InputLabel for="name" value="Nombre completo" required />

                <div class="relative mt-2">
                    <UserRound class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                    <TextInput id="name" v-model="form.name" type="text"
                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                        required autofocus autocomplete="name" />
                </div>

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Correo electrónico" required />

                <div class="relative mt-2">
                    <Mail class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                    <TextInput id="email" v-model="form.email" type="email"
                        class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-primary focus:ring-primary"
                        required autocomplete="username" />
                </div>

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null"
                class="rounded-2xl border border-secondary/30 bg-secondary/10 p-4">
                <p class="text-sm text-gray-700">
                    Tu correo electrónico aún no ha sido verificado.
                    <Link :href="route('verification.send')" method="post" as="button"
                        class="font-bold text-primary hover:text-primary-dark">
                        Reenviar correo de verificación.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'"
                    class="mt-3 flex items-center gap-2 text-sm font-semibold text-primary">
                    <CheckCircle2 class="h-4 w-4" />
                    Se envió un nuevo enlace de verificación a tu correo.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing"
                    class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow transition hover:bg-primary-dark">
                    Guardar cambios
                </PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful"
                        class="flex items-center gap-2 text-sm font-semibold text-primary">
                        <CheckCircle2 class="h-4 w-4" />
                        Guardado correctamente.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
