<script setup>
/**
 * ============================================================================
 * COMPONENTE: PrimaryActionButton
 * ============================================================================
 *
 * Botón principal reutilizable para acciones de creación.
 *
 * Uso:
 *
 * <PrimaryActionButton
 *      :href="route('catalogs.create')"
 * >
 *      <Plus class="h-4 w-4" />
 *      Nuevo catálogo
 * </PrimaryActionButton>
 *
 * ============================================================================
 */
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link :href="href"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:bg-primary-dark hover:shadow-lg">
        <slot />
    </Link>
</template>
