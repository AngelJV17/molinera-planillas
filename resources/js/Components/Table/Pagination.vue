<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Link v-for="link in links" :key="link.label" :href="link.url ?? '#'"
            class="rounded-lg border px-3 py-1 text-sm transition" :class="[
                link.active
                    ? 'border-primary bg-primary text-white'
                    : 'border-slate-200 text-gray-600 hover:bg-slate-50',
                !link.url ? 'pointer-events-none opacity-50' : ''
            ]">
            <span v-html="link.label"></span>
        </Link>
    </div>
</template>
