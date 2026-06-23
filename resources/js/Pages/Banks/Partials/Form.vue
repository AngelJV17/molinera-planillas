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
            <InputLabel for="name" value="Nombre del banco" />
            <TextInput
                id="name"
                v-model="form.name"
                class="mt-1 block w-full"
                placeholder="Ej: Banco de la Nación"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="code" value="Código" />
            <TextInput
                id="code"
                v-model="form.code"
                class="mt-1 block w-full"
                placeholder="Ej: BN"
            />
            <InputError class="mt-2" :message="form.errors.code" />
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input
                    id="status"
                    v-model="form.status"
                    type="checkbox"
                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary"
                >
                <span class="text-sm font-semibold text-gray-700">
                    Banco activo
                </span>
            </label>
            <InputError class="mt-2" :message="form.errors.status" />
        </div>
    </div>
</template>
