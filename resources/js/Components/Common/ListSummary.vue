<script setup>
const props = defineProps({
    title: {
        type: String,
        required: true,
    },

    description: {
        type: String,
        default: '',
    },

    label: {
        type: String,
        default: 'Total registros',
    },

    total: {
        type: [Number, String],
        default: 0,
    },

    icon: {
        type: [Object, Function],
        default: null,
    },
});
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
        <div>
            <h2 class="text-lg font-black text-gray-900">
                {{ title }}
            </h2>

            <p v-if="description" class="text-sm text-gray-500">
                {{ description }}
            </p>

            <slot />
        </div>

        <div class="flex items-center gap-4 rounded-2xl border border-primary/20 bg-white px-5 py-3 shadow-md">
            <div v-if="icon || $slots.icon"
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <slot name="icon">
                    <component :is="icon" class="h-5 w-5" />
                </slot>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                    {{ label }}
                </p>

                <p class="text-2xl font-black leading-none text-primary">
                    {{ total }}
                </p>
            </div>
        </div>
    </div>
</template>
