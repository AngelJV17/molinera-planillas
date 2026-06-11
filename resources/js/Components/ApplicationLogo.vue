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
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-lg ring-1 ring-primary/10">
            <img src="/images/molicente-icon.png" alt="MOLICENTE" class="h-9 w-9 object-contain" />
        </div>

        <div v-if="!collapsed" class="leading-tight">
            <h1 class="font-rajdhani text-lg font-extrabold tracking-wide"
                :class="dark ? 'text-primary' : 'text-white'">
                MOLICENTE
            </h1>

            <p class="text-xs font-medium" :class="dark ? 'text-gray-500' : 'text-white/80'">
                Industria Molinera San Vicente SRL
            </p>
        </div>
    </div>
</template>
