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

const emit = defineEmits(['toggle', 'close-mobile']);

const page = usePage();

const menuItems = [
    { name: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard },
    { name: 'Catálogos', routeName: 'catalogs.index', icon: ListChecks },
    { name: 'Trabajadores', routeName: 'workers.index', icon: Users },
    { name: 'Asistencia', routeName: 'attendance.index', icon: CalendarDays },
    { name: 'Planillas', routeName: 'payrolls.index', icon: FileSpreadsheet },
    { name: 'Boletas', routeName: 'payment-slips.index', icon: ReceiptText },
    { name: 'Reportes', routeName: 'reports.index', icon: BarChart3 },
    { name: 'Usuarios', routeName: 'users.index', icon: ShieldCheck }, 
];

/**
 * Resalta la opción activa del menú.
 */
const isActive = (routeName) => {
    return page.props.ziggy?.route_name === routeName;
};

/**
 * Cierra el drawer móvil al navegar.
 */
const handleNavigate = () => {
    emit('close-mobile');
};
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
        <div class="flex h-20 items-center justify-between border-b border-white/15 px-5"
            :class="collapsed ? 'lg:justify-center' : 'justify-between'">
            <ApplicationLogo :collapsed="collapsed" />

            <!-- Botón cerrar solo móvil -->
            <button type="button"
                class="rounded-lg p-2 text-white/80 transition hover:bg-white/15 hover:text-white lg:hidden"
                @click="emit('close-mobile')">
                <X class="h-5 w-5" />
            </button>
        </div>

        <!-- Menú -->
        <nav class="mt-6 flex-1 space-y-1 px-4">
            <Link v-for="item in menuItems" :key="item.routeName" :href="route(item.routeName)"
                class="group flex items-center rounded-xl px-4 py-3 text-sm font-semibold transition" :class="[
                    collapsed ? 'lg:justify-center' : 'gap-3',
                    isActive(item.routeName)
                        ? 'bg-white text-primary shadow-sm'
                        : 'text-white/90 hover:bg-white/15'
                ]" :title="collapsed ? item.name : ''" @click="handleNavigate">
                <component :is="item.icon" class="h-5 w-5 shrink-0" />

                <span :class="collapsed ? 'lg:hidden' : ''">
                    {{ item.name }}
                </span>
            </Link>
        </nav>

        <!-- Botón colapsar solo escritorio -->
        <div class="hidden border-t border-white/15 p-4 lg:block">
            <button type="button"
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-primary shadow transition hover:scale-105 hover:bg-secondary hover:text-white"
                @click="emit('toggle')" :title="collapsed ? 'Mostrar menú' : 'Ocultar menú'">
                <ChevronRight v-if="collapsed" class="h-5 w-5" />
                <ChevronLeft v-else class="h-5 w-5" />
            </button>
        </div>
    </aside>
</template>
