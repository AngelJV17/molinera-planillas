<script setup>
defineProps({
    align: {
        type: String,
        default: 'right',
    },
});
</script>

<template>
    <div class="flex items-center gap-2" :class="{
        'justify-end': align === 'right',
        'justify-start': align === 'left',
        'justify-center': align === 'center',
    }">
        <slot />
    </div>
</template>
