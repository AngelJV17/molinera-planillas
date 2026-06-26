<script setup>
defineProps({
    title: {
        type: [String, Number],
        required: true,
    },

    subtitle: {
        type: [String, Number],
        default: '',
    },

    meta: {
        type: [String, Number],
        default: '',
    },

    icon: {
        type: [Object, Function],
        default: null,
    },

    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex items-center gap-3">
        <div v-if="icon || $slots.icon"
            class="flex shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
            :class="compact ? 'h-9 w-9' : 'h-10 w-10'">
            <slot name="icon">
                <component :is="icon" :class="compact ? 'h-4 w-4' : 'h-5 w-5'" />
            </slot>
        </div>

        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <p class="font-bold leading-tight text-gray-800">
                    {{ title }}
                </p>

                <slot name="badge" />
            </div>

            <p v-if="subtitle" class="mt-0.5 text-xs font-medium leading-snug text-gray-500">
                {{ subtitle }}
            </p>

            <p v-if="meta" class="mt-0.5 text-xs font-semibold leading-snug text-primary">
                {{ meta }}
            </p>

            <slot />
        </div>
    </div>
</template>
