<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },

    categoryLabel: {
        type: String,
        default: 'Configuración',
    },
});
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <!-- Categoría visual -->
        <div class="md:col-span-2">
            <div class="rounded-2xl border border-primary/20 bg-primary/5 px-4 py-3">
                <p class="text-xs font-bold uppercase tracking-wide text-primary">
                    Categoría
                </p>

                <p class="mt-1 text-sm font-black text-gray-800">
                    {{ categoryLabel }}
                </p>
            </div>

            <InputError class="mt-2" :message="form.errors.type" />
        </div>

        <!-- Código -->
        <div>
            <InputLabel for="code" value="Código" required />

            <TextInput id="code" v-model="form.code" class="mt-1 block w-full" placeholder="Ej: ACTIVE" />

            <InputError class="mt-2" :message="form.errors.code" />
        </div>

        <!-- Nombre -->
        <div>
            <InputLabel for="name" value="Nombre" required />

            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" placeholder="Ej: Activo" />

            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <!-- Estado -->
        <div>
            <InputLabel for="status" value="Estado" required />

            <select id="status" v-model="form.status"
                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                <option :value="true">Activo</option>
                <option :value="false">Inactivo</option>
            </select>

            <InputError class="mt-2" :message="form.errors.status" />
        </div>

        <!-- Descripción -->
        <div class="md:col-span-2">
            <InputLabel for="description" value="Descripción" optional />

            <textarea id="description" v-model="form.description" rows="4" placeholder="Descripción opcional..."
                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"></textarea>

            <InputError class="mt-2" :message="form.errors.description" />
        </div>
    </div>
</template>
