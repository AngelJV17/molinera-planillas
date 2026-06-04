<script setup>
defineProps({
    columns: {
        type: Array,
        required: true,
    },
    emptyMessage: {
        type: String,
        default: 'No se encontraron registros.',
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-200 text-sm text-gray-800">
                        <th v-for="column in columns" :key="column.key"
                            class="px-6 py-4 font-black uppercase tracking-wide"
                            :class="column.align === 'right' ? 'text-right' : 'text-left'">
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <slot />

                    <tr v-if="$slots.empty">
                        <slot name="empty" />
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
