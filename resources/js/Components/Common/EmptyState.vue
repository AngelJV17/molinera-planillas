<script setup>
/**
 * ============================================================================
 * COMPONENTE: EmptyState
 * ============================================================================
 *
 * Uso:
 *
 * <EmptyState
 *      title="No existen bancos"
 *      description="Empieza registrando tu primer banco."
 * >
 *      <template #action>
 *          ...
 *      </template>
 * </EmptyState>
 *
 * ============================================================================
 */

import { FolderSearch } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Sin registros',
    },

    description: {
        type: String,
        default: 'No se encontraron resultados.',
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-14 text-center">
        <!-- Icono -->
        <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-3xl bg-primary/10 text-primary">
            <FolderSearch class="h-10 w-10" />
        </div>

        <!-- Título -->
        <h3 class="text-lg font-black text-gray-800">
            {{ title }}
        </h3>

        <!-- Descripción -->
        <p class="mt-2 max-w-md text-sm leading-relaxed text-gray-500">
            {{ description }}
        </p>

        <!-- Acción opcional -->
        <div v-if="$slots.action" class="mt-6">
            <slot name="action" />
        </div>
    </div>
</template>
