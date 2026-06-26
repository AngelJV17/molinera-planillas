<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        default: null,
    },

    icon: {
        type: [Object, Function],
        default: null,
    },

    title: {
        type: String,
        default: '',
    },

    variant: {
        type: String,
        default: 'primary',
    },

    disabled: {
        type: Boolean,
        default: false,
    },

    method: {
        type: String,
        default: 'get',
    },

    as: {
        type: String,
        default: 'a',
    },

    preserveScroll: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['click']);

const buttonClasses = computed(() => {
    const variants = {
        primary: 'text-gray-700 hover:border-primary hover:bg-primary/10 hover:text-primary',
        danger: 'text-gray-700 hover:border-danger hover:bg-danger/10 hover:text-danger',
        warning: 'text-gray-700 hover:border-amber-500 hover:bg-amber-50 hover:text-amber-700',
        success: 'text-gray-700 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700',
        neutral: 'text-gray-700 hover:border-slate-400 hover:bg-slate-100 hover:text-slate-800',
    };

    return variants[props.variant] ?? variants.primary;
});

const baseClasses = computed(() => [
    'inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 bg-white shadow-sm transition',
    buttonClasses.value,
    props.disabled ? 'cursor-not-allowed opacity-50 hover:border-slate-300 hover:bg-white hover:text-gray-700' : '',
]);

const handleClick = (event) => {
    if (props.disabled) {
        event.preventDefault();
        return;
    }

    emit('click', event);
};
</script>

<template>
    <Link v-if="href" :href="href" :method="method" :as="as" :preserve-scroll="preserveScroll" :class="baseClasses"
        :title="title" :aria-label="title" @click="handleClick">
        <slot>
            <component v-if="icon" :is="icon" class="h-4 w-4" />
        </slot>
    </Link>

    <button v-else type="button" :class="baseClasses" :title="title" :aria-label="title" :disabled="disabled"
        @click="handleClick">
        <slot>
            <component v-if="icon" :is="icon" class="h-4 w-4" />
        </slot>
    </button>
</template>
