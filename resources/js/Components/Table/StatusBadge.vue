<script setup>
defineProps({
    status: {
        type: Boolean,
        required: true,
    },
});
</script>

<template>
    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="status
        ? 'bg-primary/15 text-primary'
        : 'bg-danger/15 text-danger'">
        {{ status ? 'Activo' : 'Inactivo' }}
    </span>
</template>
