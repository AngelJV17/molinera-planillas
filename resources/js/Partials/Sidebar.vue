<script setup>
import { Link, usePage } from '@inertiajs/vue3';

import {
    LayoutDashboard,
    Users,
    CalendarDays,
    FileSpreadsheet,
    ReceiptText,
    BarChart3,
    ShieldCheck,
    ListChecks,
    ChevronLeft,
    ChevronRight,
    X,
    Building2,
    Clock3,
} from 'lucide-vue-next';

import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },

    mobileOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'toggle',
    'close-mobile',
]);

const page = usePage();

/**
 * ============================================================================
 * MENÚ PRINCIPAL DEL SISTEMA
 * ============================================================================
 *
 * Organización basada en áreas funcionales del negocio.
 *
 * Esto permite:
 * - Escalabilidad
 * - Mejor experiencia de usuario
 * - Menús empresariales más ordenados
 *
 * ============================================================================
 */
const menuItems = [
    {
        name: 'Dashboard',
        routeName: 'dashboard',
        icon: LayoutDashboard,
        section: 'dashboard',
        activePattern: 'dashboard.*',
    },

    {
        name: 'Trabajadores',
        routeName: 'dashboard',
        icon: Users,
        section: 'personal',
        activePattern: 'employees.*',
    },

    {
        name: 'Turnos',
        routeName: 'dashboard',
        icon: Clock3,
        section: 'personal',
        activePattern: 'shifts.*',
    },

    {
        name: 'Asistencias',
        routeName: 'attendance.index',
        icon: CalendarDays,
        section: 'personal',
        activePattern: 'attendance.*',
    },

    {
        name: 'Planillas',
        routeName: 'payrolls.index',
        icon: FileSpreadsheet,
        section: 'payroll',
        activePattern: 'payrolls.*',
    },

    {
        name: 'Boletas',
        routeName: 'payment-slips.index',
        icon: ReceiptText,
        section: 'payroll',
        activePattern: 'payment-slips.*',
    },

    {
        name: 'Configuraciones',
        routeName: 'catalogs.index',
        icon: ListChecks,
        section: 'settings',
        activePattern: 'catalogs.*',
    },

    {
        name: 'Bancos',
        routeName: 'banks.index',
        icon: Building2,
        section: 'settings',
        activePattern: 'banks.*',
    },

    {
        name: 'Usuarios',
        routeName: 'users.index',
        icon: ShieldCheck,
        section: 'security',
        activePattern: 'users.*',
    },

    {
        name: 'Reportes',
        routeName: 'reports.index',
        icon: BarChart3,
        section: 'reports',
        activePattern: 'reports.*',
    },
];

/**
 * Determina si la ruta actual coincide con el menú.
 */
const isActive = (item) => {
    return route().current(item.activePattern);
};

/**
 * Cierra el menú móvil al navegar.
 */
const handleNavigate = () => {
    emit('close-mobile');
};

/**
 * Determina cuándo mostrar separadores visuales.
 */
const showDivider = (index) => {
    if (index === 0) return false;

    return (
        menuItems[index].section !==
        menuItems[index - 1].section
    );
};

console.log(route().current());
</script>

<template>
    <aside
        class="fixed left-0 top-0 z-50 flex h-screen flex-col bg-primary text-white shadow-xl transition-all duration-300"
        :class="[
            collapsed ? 'lg:w-24' : 'lg:w-72',
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            'w-72'
        ]">
        <!-- Cabecera sidebar -->
        <div class="flex h-20 items-center justify-between border-b border-white/15 px-5">
            <ApplicationLogo :collapsed="collapsed" />

            <!-- Botón cerrar solo móvil -->
            <button type="button"
                class="rounded-lg p-2 text-white/80 transition hover:bg-white/15 hover:text-white lg:hidden"
                @click="emit('close-mobile')">
                <X class="h-5 w-5" />
            </button>
        </div>

        <!-- Menú -->
        <nav class="sidebar-scroll mt-2 flex-1 overflow-y-auto px-3">
            <template v-for="(item, index) in menuItems" :key="item.routeName">
                <!-- Separador elegante -->
                <div v-if="showDivider(index)" class="my-2 border-t border-white/10" />

                <Link :href="route(item.routeName)"
                    class="group flex items-center rounded-xl px-3 py-2.5 text-xs font-semibold transition-all duration-200"
                    :class="[
                        collapsed ? 'lg:justify-center' : 'gap-3',

                        isActive(item)
                            ? 'bg-white text-primary shadow-md'
                            : 'text-white/90 hover:bg-white/10'
                    ]" :title="collapsed ? item.name : ''" @click="handleNavigate">
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />

                    <span :class="collapsed ? 'lg:hidden' : ''">
                        {{ item.name }}
                    </span>
                </Link>
            </template>
        </nav>

        <!-- Botón colapsar solo escritorio -->
        <div class="hidden border-t border-white/10 p-3 lg:block">
            <button type="button"
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-primary shadow transition hover:scale-105 hover:bg-secondary hover:text-white"
                @click="emit('toggle')" :title="collapsed ? 'Mostrar menú' : 'Ocultar menú'">
                <ChevronRight v-if="collapsed" class="h-5 w-5" />
                <ChevronLeft v-else class="h-5 w-5" />
            </button>
        </div>
    </aside>
</template>
