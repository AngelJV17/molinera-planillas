<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    CheckCircle2,
    KeyRound,
    ListChecks,
    Save,
    ShieldAlert,
    ShieldCheck,
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

    permissions: {
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

    isProtected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['submit']);

const currentStep = ref(1);

const steps = [
    {
        number: 1,
        title: 'Información',
        description: 'Datos principales del rol',
        icon: ShieldCheck,
    },
    {
        number: 2,
        title: 'Permisos',
        description: 'Accesos por módulo',
        icon: KeyRound,
    },
    {
        number: 3,
        title: 'Confirmación',
        description: 'Resumen final',
        icon: CheckCircle2,
    },
];

const totalPermissions = computed(() => {
    return props.permissions.reduce((total, group) => {
        return total + (group.permissions?.length ?? 0);
    }, 0);
});

const selectedPermissionsCount = computed(() => {
    return props.form.permissions.length;
});

const selectedModuleGroups = computed(() => {
    return props.permissions
        .map((group) => {
            const selected = group.permissions.filter((permission) => {
                return props.form.permissions.includes(permission.name);
            });

            return {
                ...group,
                selected,
            };
        })
        .filter((group) => group.selected.length > 0);
});

const canContinueFromStepOne = computed(() => {
    return props.form.name?.trim()?.length > 0;
});

const isPermissionSelected = (permissionName) => {
    return props.form.permissions.includes(permissionName);
};

const togglePermission = (permissionName) => {
    if (isPermissionSelected(permissionName)) {
        props.form.permissions = props.form.permissions.filter((permission) => permission !== permissionName);
        return;
    }

    props.form.permissions = [
        ...props.form.permissions,
        permissionName,
    ];
};

const modulePermissionNames = (group) => {
    return group.permissions.map((permission) => permission.name);
};

const moduleSelectedCount = (group) => {
    const names = modulePermissionNames(group);

    return names.filter((permission) => props.form.permissions.includes(permission)).length;
};

const isModuleFullySelected = (group) => {
    const names = modulePermissionNames(group);

    return names.length > 0 && names.every((permission) => props.form.permissions.includes(permission));
};

const isModulePartiallySelected = (group) => {
    return moduleSelectedCount(group) > 0 && !isModuleFullySelected(group);
};

const toggleModule = (group) => {
    const names = modulePermissionNames(group);

    if (isModuleFullySelected(group)) {
        props.form.permissions = props.form.permissions.filter((permission) => !names.includes(permission));
        return;
    }

    props.form.permissions = Array.from(new Set([
        ...props.form.permissions,
        ...names,
    ]));
};

const goToStep = (step) => {
    if (step === 2 && !canContinueFromStepOne.value) {
        currentStep.value = 1;
        return;
    }

    if (step === 3 && !canContinueFromStepOne.value) {
        currentStep.value = 1;
        return;
    }

    currentStep.value = step;
};

const nextStep = () => {
    if (currentStep.value === 1 && !canContinueFromStepOne.value) {
        return;
    }

    if (currentStep.value < 3) {
        currentStep.value += 1;
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value -= 1;
    }
};

const handleSubmit = () => {
    if (currentStep.value < 3) {
        nextStep();
        return;
    }

    emit('submit');
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="handleSubmit">
        <!-- Steps -->
        <div class="rounded-3xl border border-slate-300 bg-white p-5 shadow-lg">
            <div class="grid gap-3 md:grid-cols-3">
                <button v-for="step in steps" :key="step.number" type="button"
                    class="flex items-center gap-4 rounded-2xl border px-4 py-4 text-left transition" :class="[
                        currentStep === step.number
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'border-slate-200 bg-slate-50 text-gray-600 hover:border-primary/30 hover:bg-primary/5'
                    ]" @click="goToStep(step.number)">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="[
                        currentStep === step.number
                            ? 'bg-primary text-white'
                            : 'bg-white text-primary'
                    ]">
                        <component :is="step.icon" class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-sm font-black">
                            Paso {{ step.number }}: {{ step.title }}
                        </p>

                        <p class="mt-0.5 text-xs font-medium text-gray-500">
                            {{ step.description }}
                        </p>
                    </div>
                </button>
            </div>
        </div>

        <!-- Paso 1 -->
        <div v-show="currentStep === 1">
            <SectionCard title="Información del rol"
                description="Define el nombre del perfil de acceso que será asignado a los usuarios del sistema.">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Nombre del rol" />

                        <TextInput id="name" v-model="form.name" type="text" class="mt-2 block w-full"
                            placeholder="Ej. Supervisor de asistencia" :disabled="isProtected" autofocus />

                        <InputError class="mt-2" :message="form.errors.name" />

                        <p v-if="isProtected"
                            class="mt-2 rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 text-xs font-medium leading-relaxed text-primary">
                            Este rol es protegido. Su nombre no puede modificarse para evitar problemas en la seguridad
                            del sistema.
                        </p>

                        <p v-if="!canContinueFromStepOne" class="mt-2 text-xs font-medium text-gray-500">
                            Ingresa un nombre para continuar con la asignación de permisos.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <ShieldAlert class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-sm font-black text-gray-900">
                                    Recomendación de seguridad
                                </p>

                                <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                    Asigna solo los permisos necesarios para cada rol. Evita entregar accesos
                                    administrativos a usuarios operativos.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </SectionCard>
        </div>

        <!-- Paso 2 -->
        <div v-show="currentStep === 2">
            <SectionCard title="Permisos del rol"
                description="Selecciona las acciones que podrá realizar este rol dentro de cada módulo del sistema.">
                <div class="mb-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-primary/20 bg-primary/5 p-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <KeyRound class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                    Permisos seleccionados
                                </p>

                                <p class="text-2xl font-black leading-none text-primary">
                                    {{ selectedPermissionsCount }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <ListChecks class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                    Permisos disponibles
                                </p>

                                <p class="text-2xl font-black leading-none text-gray-900">
                                    {{ totalPermissions }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <InputError class="mb-4" :message="form.errors.permissions" />

                <div v-if="permissions.length" class="space-y-5">
                    <div v-for="group in permissions" :key="group.module"
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-black text-gray-900">
                                    {{ group.label }}
                                </h3>

                                <p class="text-xs font-medium text-gray-500">
                                    {{ moduleSelectedCount(group) }} de {{ group.permissions.length }} permisos
                                    seleccionados.
                                </p>
                            </div>

                            <button type="button"
                                class="inline-flex items-center justify-center rounded-xl border px-4 py-2 text-xs font-black uppercase tracking-wide transition"
                                :class="[
                                    isModuleFullySelected(group)
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-slate-300 bg-white text-gray-600 hover:border-primary hover:bg-primary/5 hover:text-primary'
                                ]" @click="toggleModule(group)">
                                <span v-if="isModuleFullySelected(group)">
                                    Quitar todos
                                </span>

                                <span v-else-if="isModulePartiallySelected(group)">
                                    Completar selección
                                </span>

                                <span v-else>
                                    Seleccionar todos
                                </span>
                            </button>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <label v-for="permission in group.permissions" :key="permission.name"
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition" :class="[
                                    isPermissionSelected(permission.name)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-slate-200 bg-slate-50 hover:border-primary/30 hover:bg-primary/5'
                                ]">
                                <input type="checkbox"
                                    class="mt-1 rounded border-slate-300 text-primary shadow-sm focus:ring-primary"
                                    :checked="isPermissionSelected(permission.name)"
                                    @change="togglePermission(permission.name)" />

                                <div class="min-w-0">
                                    <p class="text-sm font-black text-gray-800">
                                        {{ permission.label }}
                                    </p>

                                    <p class="mt-0.5 break-all text-[11px] font-semibold text-gray-500">
                                        {{ permission.name }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div v-else
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-medium text-amber-800">
                    No se encontraron permisos registrados. Ejecuta primero el seeder de roles y permisos.
                </div>
            </SectionCard>
        </div>

        <!-- Paso 3 -->
        <div v-show="currentStep === 3">
            <SectionCard title="Resumen y confirmación"
                description="Revisa la configuración antes de guardar los cambios del rol.">
                <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                    <div class="rounded-3xl border border-primary/20 bg-primary/5 p-6">
                        <div class="flex items-start gap-4">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <ShieldCheck class="h-6 w-6" />
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                    Rol configurado
                                </p>

                                <h3 class="mt-1 text-xl font-black text-gray-900">
                                    {{ form.name || 'Sin nombre' }}
                                </h3>

                                <p class="mt-2 text-sm leading-relaxed text-gray-500">
                                    Este rol tendrá
                                    <strong class="text-primary">
                                        {{ selectedPermissionsCount }}
                                    </strong>
                                    permisos asignados.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-black text-gray-900">
                            Módulos con permisos asignados
                        </h3>

                        <div v-if="selectedModuleGroups.length" class="mt-4 space-y-3">
                            <div v-for="group in selectedModuleGroups" :key="group.module"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-black text-gray-800">
                                            {{ group.label }}
                                        </p>

                                        <p class="text-xs text-gray-500">
                                            {{ group.selected.length }} permisos seleccionados.
                                        </p>
                                    </div>

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <Check class="h-4 w-4" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
                            Este rol no tiene permisos seleccionados. Puede guardarse, pero el usuario no tendrá acceso
                            operativo a los módulos.
                        </div>
                    </div>
                </div>
            </SectionCard>
        </div>

        <!-- Acciones -->
        <div
            class="flex flex-col-reverse gap-3 rounded-3xl border border-slate-300 bg-white p-5 shadow-lg sm:flex-row sm:items-center sm:justify-between">
            <Link :href="route('roles.index')"
                class="inline-flex justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition hover:bg-slate-50">
                Cancelar
            </Link>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                <button v-if="currentStep > 1" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-gray-700 transition hover:bg-slate-50"
                    @click="previousStep">
                    <ArrowLeft class="h-4 w-4" />
                    Anterior
                </button>

                <button v-if="currentStep < 3" type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="currentStep === 1 && !canContinueFromStepOne" @click="nextStep">
                    Siguiente
                    <ArrowRight class="h-4 w-4" />
                </button>

                <button v-else type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg transition hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="form.processing">
                    <Save class="h-4 w-4" />

                    <span>
                        {{ form.processing ? 'Guardando...' : submitLabel }}
                    </span>
                </button>
            </div>
        </div>
    </form>
</template>
