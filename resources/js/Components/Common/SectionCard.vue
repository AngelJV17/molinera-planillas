<script setup>
/**
 * ============================================================================
 * COMPONENTE: SectionCard
 * ============================================================================
 *
 * Contenedor estándar para secciones.
 */

defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <div class="rounded-3xl border border-slate-300 bg-white shadow-lg">

        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
            <h3 class="font-black text-gray-800">
                {{ title }}
            </h3>

            <p v-if="description" class="mt-1 text-xs text-gray-500">
                {{ description }}
            </p>
        </div>

        <div class="p-6">
            <slot />
        </div>

    </div>
</template>
