<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: 10,
    },
});

const emit = defineEmits([
    'update:modelValue',
]);
</script>

<template>
    <select :value="modelValue" @change="emit('update:modelValue', $event.target.value)"
        class="rounded-xl border-slate-300 bg-white text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary">
        <option value="10">
            10 por página
        </option>

        <option value="25">
            25 por página
        </option>

        <option value="50">
            50 por página
        </option>

        <option value="100">
            100 por página
        </option>
    </select>
</template>
