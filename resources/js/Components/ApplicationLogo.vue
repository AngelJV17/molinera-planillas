<script setup>
defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
    dark: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl shadow-sm"
            :class="dark ? 'bg-primary text-white' : 'bg-white text-primary'">
            <span class="text-xl font-black">M</span>
        </div>

        <div v-if="!collapsed" class="leading-tight">
            <h1 class="text-lg font-extrabold tracking-wide" :class="dark ? 'text-primary' : 'text-white'">
                MOLICENTE
            </h1>

            <p class="text-xs font-medium" :class="dark ? 'text-gray-500' : 'text-white/80'">
                Industria Molinera San Vicente SRL
            </p>
        </div>
    </div>
</template>
