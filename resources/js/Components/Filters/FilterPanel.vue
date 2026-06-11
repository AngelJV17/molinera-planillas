<script setup>
import { SlidersHorizontal } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Filtros de búsqueda',
    },

    description: {
        type: String,
        default: 'Refina los resultados del listado.',
    },
});
</script>

<template>
    <div class="rounded-3xl border border-slate-300 bg-white shadow-lg">

        <!-- Encabezado -->
        <div class="flex items-center gap-3 border-b border-slate-200 bg-slate-100 px-6 py-4">

            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-primary">
                <slot name="icon">
                    <SlidersHorizontal class="h-5 w-5" />
                </slot>
            </div>

            <div>
                <h2 class="text-sm font-black uppercase tracking-wide text-gray-800">
                    {{ title }}
                </h2>

                <p class="text-xs text-gray-500">
                    {{ description }}
                </p>
            </div>

        </div>

        <!-- Contenido -->
        <div class="grid gap-4 p-6 md:grid-cols-3">
            <slot />
        </div>

    </div>
</template>
