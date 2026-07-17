<script setup>
defineProps({
    value: {
        type: String,
    },
    required: {
        type: Boolean,
        default: false,
    },
    optional: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <label class="inline-flex min-h-5 flex-wrap items-center gap-2 text-sm font-semibold text-gray-700">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>

        <span
            v-if="required"
            class="rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-red-700 ring-1 ring-red-100"
        >
            Obligatorio
        </span>

        <span
            v-else-if="optional"
            class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-slate-500 ring-1 ring-slate-200"
        >
            Opcional
        </span>
    </label>
</template>
