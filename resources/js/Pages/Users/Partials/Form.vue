<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    KeyRound,
    Mail,
    Save,
    ShieldCheck,
    User,
    UserRound,
} from 'lucide-vue-next';

import SectionCard from '@/Components/Common/SectionCard.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },

    roles: {
        type: Array,
        default: () => [],
    },

    submitLabel: {
        type: String,
        default: 'Guardar',
    },

    isEdit: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['submit']);

const selectedRole = computed({
    get() {
        return props.form.roles?.[0] ?? '';
    },

    set(value) {
        props.form.roles = value ? [value] : [];
    },
});
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <SectionCard title="Datos del usuario"
            description="Registra la información principal de la cuenta de acceso al sistema.">
            <div class="grid gap-6 lg:grid-cols-2">
                <div>
                    <InputLabel for="name" value="Nombre completo" required />

                    <div class="relative mt-2">
                        <UserRound
                            class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                        <TextInput id="name" v-model="form.name" type="text" class="block w-full pl-11"
                            placeholder="Ej. Juan Carlos Pérez" autofocus />
                    </div>

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="username" value="Usuario" required />

                    <div class="relative mt-2">
                        <User
                            class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                        <TextInput id="username" v-model="form.username" type="text" class="block w-full pl-11"
                            placeholder="Ej. jperez" />
                    </div>

                    <InputError class="mt-2" :message="form.errors.username" />
                </div>

                <div>
                    <InputLabel for="email" value="Correo electrónico" optional />

                    <div class="relative mt-2">
                        <Mail
                            class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                        <TextInput id="email" v-model="form.email" type="email" class="block w-full pl-11"
                            placeholder="Ej. usuario@molicente.com" />
                    </div>

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="status" value="Estado" required />

                    <select id="status" v-model="form.status"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
                        <option :value="true">
                            Activo
                        </option>

                        <option :value="false">
                            Inactivo
                        </option>
                    </select>

                    <InputError class="mt-2" :message="form.errors.status" />
                </div>
            </div>
        </SectionCard>

        <SectionCard :title="isEdit ? 'Contraseña del usuario' : 'Contraseña temporal'" :description="isEdit
            ? 'La contraseña no se modifica desde este formulario.'
            : 'La contraseña será generada automáticamente por el sistema al registrar el usuario.'">
            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-5">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <KeyRound class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-sm font-black text-gray-900">
                            {{ isEdit ? 'Gestión segura de contraseña' : 'Generación automática' }}
                        </p>

                        <p v-if="!isEdit" class="mt-1 text-xs leading-relaxed text-gray-500">
                            El sistema generará una contraseña temporal con prefijo corporativo, por ejemplo:
                            <strong class="text-primary">MOLI-K7PD-4821@</strong>.
                            Esta contraseña se mostrará una sola vez después de crear el usuario.
                        </p>

                        <p v-else class="mt-1 text-xs leading-relaxed text-gray-500">
                            Para proteger la cuenta, la contraseña no se edita aquí. Si el usuario olvida su clave,
                            se debe generar una nueva contraseña temporal desde la opción de restablecimiento.
                        </p>
                    </div>
                </div>
            </div>
        </SectionCard>

        <SectionCard title="Rol del usuario"
            description="Selecciona el perfil de acceso que tendrá este usuario dentro del sistema.">
            <div class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
                <div>
                    <InputLabel for="role" value="Rol asignado" required />

                    <div class="relative mt-2">
                        <ShieldCheck
                            class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                        <select id="role" v-model="selectedRole"
                            class="block w-full rounded-xl border-slate-300 bg-white pl-11 text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">
                                Selecciona un rol
                            </option>

                            <option v-for="role in roles" :key="role.id" :value="role.name">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>

                    <InputError class="mt-2" :message="form.errors.roles" />

                    <InputError class="mt-2" :message="form.errors['roles.0']" />
                </div>

                <div class="rounded-2xl border border-primary/20 bg-primary/5 p-5">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <KeyRound class="h-5 w-5" />
                        </div>

                        <div>
                            <p class="text-sm font-black text-gray-900">
                                Asignación de acceso
                            </p>

                            <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                El rol define los permisos que tendrá el usuario. Para mantener el control del sistema,
                                asigna un solo rol principal por usuario.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </SectionCard>

        <div
            class="flex flex-col-reverse gap-3 rounded-3xl border border-slate-300 bg-white p-5 shadow-lg sm:flex-row sm:items-center sm:justify-end">
            <Link :href="route('users.index')"
                class="inline-flex justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition hover:bg-slate-50">
                Cancelar
            </Link>

            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="form.processing">
                <Save class="h-4 w-4" />

                <span>
                    {{ form.processing ? 'Guardando...' : submitLabel }}
                </span>
            </button>
        </div>
    </form>
</template>
