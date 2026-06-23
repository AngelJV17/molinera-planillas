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
        <div>
            <InputLabel for="type" value="Tipo" />
            <TextInput
                id="type"
                v-model="form.type"
                class="mt-1 block w-full"
                placeholder="Ej: WORKER_STATUS"
            />
            <InputError class="mt-2" :message="form.errors.type" />
        </div>

        <div>
            <InputLabel for="code" value="Código" />
            <TextInput
                id="code"
                v-model="form.code"
                class="mt-1 block w-full"
                placeholder="Ej: ACTIVE"
            />
            <InputError class="mt-2" :message="form.errors.code" />
        </div>

        <div>
            <InputLabel for="name" value="Nombre" />
            <TextInput
                id="name"
                v-model="form.name"
                class="mt-1 block w-full"
                placeholder="Ej: Activo"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="status" value="Estado" />
            <select
                id="status"
                v-model="form.status"
                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
            >
                <option :value="true">Activo</option>
                <option :value="false">Inactivo</option>
            </select>
            <InputError class="mt-2" :message="form.errors.status" />
        </div>

        <div class="md:col-span-2">
            <InputLabel for="description" value="Descripción" />
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                placeholder="Descripción opcional..."
                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
            ></textarea>
            <InputError class="mt-2" :message="form.errors.description" />
        </div>
    </div>
</template>
