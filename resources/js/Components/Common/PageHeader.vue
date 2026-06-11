<script setup>
/**
 * ============================================================================
 * COMPONENTE: PageHeader
 * ============================================================================
 *
 * Encabezado reutilizable para todos los módulos del sistema.
 *
 * Características:
 * - Icono configurable
 * - Título
 * - Descripción
 * - Acciones personalizadas
 * - Estadísticas opcionales
 * - Responsive
 *
 * Autor: Equipo MOLICENTE
 * ============================================================================
 */

defineProps({
    title: {
        type: String,
        required: true,
    },

    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-3xl border border-primary/20 bg-white shadow-lg">
        <!-- Barra superior -->
        <div class="h-1.5 bg-primary"></div>

        <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-center lg:justify-between">
            <!-- Información -->
            <div class="flex gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary">
                    <slot name="icon" />
                </div>

                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-900">
                        {{ title }}
                    </h1>

                    <p v-if="description" class="mt-1 max-w-3xl text-sm leading-relaxed text-gray-600">
                        {{ description }}
                    </p>

                    <!-- Estadísticas opcionales -->
                    <div v-if="$slots.stats" class="mt-3">
                        <slot name="stats" />
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-3">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
