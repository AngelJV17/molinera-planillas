<script setup>
import { ref } from 'vue';
import Sidebar from '@/Partials/Sidebar.vue';
import Header from '@/Partials/Header.vue';
import Footer from '@/Partials/Footer.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

/**
 * Controla el sidebar colapsado en escritorio.
 */
const sidebarCollapsed = ref(false);

/**
 * Controla el menú móvil.
 */
const mobileSidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Sidebar desktop -->
        <Sidebar class="hidden lg:flex" :collapsed="sidebarCollapsed" :mobile-open="false"
            @toggle="sidebarCollapsed = !sidebarCollapsed" />

        <!-- Overlay móvil -->
        <div v-if="mobileSidebarOpen" class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            @click="mobileSidebarOpen = false"></div>

        <!-- Sidebar móvil tipo drawer -->
        <Sidebar class="lg:hidden" :collapsed="false" :mobile-open="mobileSidebarOpen"
            @close-mobile="mobileSidebarOpen = false" />

        <!-- Contenido -->
        <div class="flex min-h-screen flex-col transition-all duration-300"
            :class="sidebarCollapsed ? 'lg:pl-24' : 'lg:pl-72'">
            <Header :title="title" @open-mobile-sidebar="mobileSidebarOpen = true" />

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>

            <Footer />
        </div>
    </div>
</template>
