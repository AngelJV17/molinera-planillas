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
            <InputLabel for="code" value="Codigo tecnico" />
            <TextInput
                id="code"
                v-model="form.code"
                class="mt-1 block w-full"
                placeholder="Ej: ONP_RATE"
            />
            <p class="mt-2 text-xs font-semibold text-gray-500">
                Este codigo es usado por el calculo de planillas.
            </p>
            <InputError class="mt-2" :message="form.errors.code" />
        </div>

        <div>
            <InputLabel for="name" value="Nombre visible" />
            <TextInput
                id="name"
                v-model="form.name"
                class="mt-1 block w-full"
                placeholder="Ej: Tasa ONP"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="value" value="Valor" />
            <TextInput
                id="value"
                v-model="form.value"
                type="number"
                min="0"
                step="0.0001"
                class="mt-1 block w-full"
                placeholder="Ej: 0.1300"
            />
            <InputError class="mt-2" :message="form.errors.value" />
        </div>

        <div>
            <InputLabel for="effective_from" value="Vigente desde" />
            <TextInput
                id="effective_from"
                v-model="form.effective_from"
                type="date"
                class="mt-1 block w-full"
            />
            <InputError class="mt-2" :message="form.errors.effective_from" />
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
            <InputLabel for="description" value="Descripcion" />
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                placeholder="Describe cuando y como se usa este parametro."
            />
            <InputError class="mt-2" :message="form.errors.description" />
        </div>
    </div>
</template>
