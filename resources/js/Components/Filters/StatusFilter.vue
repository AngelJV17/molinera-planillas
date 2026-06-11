<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
});

const emit = defineEmits([
    'update:modelValue',
]);
</script>

<template>
    <select :value="modelValue" @change="emit('update:modelValue', $event.target.value)"
        class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
        <option value="">
            Todos los estados
        </option>

        <option value="1">
            Activos
        </option>

        <option value="0">
            Inactivos
        </option>
    </select>
</template>
