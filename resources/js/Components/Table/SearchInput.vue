<script setup>
import { Search } from 'lucide-vue-next';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Buscar...',
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="relative">
        <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />

        <input :value="modelValue" type="text" :placeholder="placeholder"
            class="w-full rounded-xl border-gray-300 pl-10 text-sm focus:border-primary focus:ring-primary"
            @input="emit('update:modelValue', $event.target.value)" />
    </div>
</template>
