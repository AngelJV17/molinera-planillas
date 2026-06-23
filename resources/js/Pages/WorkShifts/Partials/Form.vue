<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <!-- Nombre del turno -->
        <div>
            <InputLabel for="name" value="Nombre del turno" />

            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" placeholder="Ej: Turno administrativo" />

            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <!-- Horas efectivas de jornada -->
        <div>
            <InputLabel for="daily_hours" value="Horas efectivas de jornada" />

            <TextInput id="daily_hours" v-model="form.daily_hours" type="number" min="0.5" max="24" step="0.25"
                class="mt-1 block w-full" placeholder="Ej: 8" />

            <InputError class="mt-2" :message="form.errors.daily_hours" />
        </div>

        <!-- Hora de entrada -->
        <div>
            <InputLabel for="start_time" value="Hora de entrada" />

            <TextInput id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full" />

            <InputError class="mt-2" :message="form.errors.start_time" />
        </div>

        <!-- Hora de salida -->
        <div>
            <InputLabel for="end_time" value="Hora de salida" />

            <TextInput id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full" />

            <InputError class="mt-2" :message="form.errors.end_time" />
        </div>

        <!-- Inicio de refrigerio / almuerzo -->
        <div>
            <InputLabel for="break_start_time" value="Inicio de almuerzo" />

            <TextInput id="break_start_time" v-model="form.break_start_time" type="time" class="mt-1 block w-full" />

            <InputError class="mt-2" :message="form.errors.break_start_time" />
        </div>

        <!-- Fin de refrigerio / almuerzo -->
        <div>
            <InputLabel for="break_end_time" value="Fin de almuerzo" />

            <TextInput id="break_end_time" v-model="form.break_end_time" type="time" class="mt-1 block w-full" />

            <InputError class="mt-2" :message="form.errors.break_end_time" />
        </div>

        <!-- Minutos de tolerancia -->
        <div>
            <InputLabel for="tolerance_minutes" value="Minutos de tolerancia" />

            <TextInput id="tolerance_minutes" v-model="form.tolerance_minutes" type="number" min="0" max="240" step="1"
                class="mt-1 block w-full" placeholder="Ej: 10" />

            <InputError class="mt-2" :message="form.errors.tolerance_minutes" />
        </div>

        <!-- Controles del turno -->
        <div class="space-y-3">
            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input id="crosses_midnight" v-model="form.crosses_midnight" type="checkbox"
                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">

                <span class="text-sm font-semibold text-gray-700">
                    Termina al día siguiente
                </span>
            </label>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input id="status" v-model="form.status" type="checkbox"
                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">

                <span class="text-sm font-semibold text-gray-700">
                    Turno activo
                </span>
            </label>

            <InputError :message="form.errors.crosses_midnight" />
            <InputError :message="form.errors.status" />
        </div>

        <!-- Descripción -->
        <div class="md:col-span-2">
            <InputLabel for="description" value="Descripción" />

            <textarea id="description" v-model="form.description" rows="4" placeholder="Detalle opcional del turno..."
                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"></textarea>

            <InputError class="mt-2" :message="form.errors.description" />
        </div>
    </div>
</template>
