<script setup>
import { ref, onMounted, watch } from "vue";

import Sidebar from "@/Partials/Sidebar.vue";
import Header from "@/Partials/Header.vue";
import Footer from "@/Partials/Footer.vue";
import AppAlertHandler from "@/Components/Common/AppAlertHandler.vue";

defineProps({
    title: {
        type: String,
        default: "Dashboard",
    },
});

/**
 * ============================================================================
 * SIDEBAR ESCRITORIO
 * ============================================================================
 *
 * Se almacena en localStorage para mantener el estado
 * entre navegaciones de Inertia y recargas del navegador.
 *
 * true  = sidebar colapsado
 * false = sidebar expandido
 *
 * ============================================================================
 */
const sidebarCollapsed = ref(false);

/**
 * ============================================================================
 * SIDEBAR MÓVIL
 * ============================================================================
 */
const mobileSidebarOpen = ref(false);

/**
 * Recupera la preferencia guardada del usuario.
 */
onMounted(() => {
    sidebarCollapsed.value =
        localStorage.getItem("sidebarCollapsed") === "true";
});

/**
 * Guarda automáticamente cualquier cambio.
 */
watch(sidebarCollapsed, (value) => {
    localStorage.setItem("sidebarCollapsed", value.toString());
});

/**
 * Alterna el estado del sidebar.
 */
const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- ==========================================================
             ALERTAS
        =========================================================== -->
        <AppAlertHandler />

        <!-- ==========================================================
             SIDEBAR DESKTOP
        =========================================================== -->
        <Sidebar
            class="hidden lg:flex"
            :collapsed="sidebarCollapsed"
            :mobile-open="false"
            @toggle="toggleSidebar"
        />

        <!-- ==========================================================
             OVERLAY MÓVIL
        =========================================================== -->
        <div
            v-if="mobileSidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            @click="mobileSidebarOpen = false"
        />

        <!-- ==========================================================
             SIDEBAR MÓVIL
        =========================================================== -->
        <Sidebar
            class="lg:hidden"
            :collapsed="false"
            :mobile-open="mobileSidebarOpen"
            @close-mobile="mobileSidebarOpen = false"
        />

        <!-- ==========================================================
             CONTENIDO PRINCIPAL
        =========================================================== -->
        <div
            class="flex min-h-screen flex-col transition-all duration-300"
            :class="sidebarCollapsed ? 'lg:pl-24' : 'lg:pl-64'"
        >
            <Header
                :title="title"
                @open-mobile-sidebar="mobileSidebarOpen = true"
            />

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>

            <Footer />
        </div>
    </div>
</template>
