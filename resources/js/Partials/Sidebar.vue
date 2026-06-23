<script setup>
import { ref, onMounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

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
    BriefcaseBusiness,
} from 'lucide-vue-next';

import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
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

const menuItems = [
    { name: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard, section: 'dashboard', activePattern: 'dashboard' },
    { name: 'Trabajadores', routeName: 'workers.index', icon: Users, section: 'personal', activePattern: 'workers.*' },
    { name: 'Asistencias', routeName: 'attendance.index', icon: CalendarDays, section: 'personal', activePattern: 'attendance.*' },
    { name: 'Planillas', routeName: 'payrolls.index', icon: FileSpreadsheet, section: 'payroll', activePattern: 'payrolls.*' },
    { name: 'Boletas', routeName: 'payment-slips.index', icon: ReceiptText, section: 'payroll', activePattern: 'payment-slips.*' },
    { name: 'Configuraciones', routeName: 'catalogs.index', icon: ListChecks, section: 'settings', activePattern: 'catalogs.*' },

    {
        name: 'Estructura Organizacional',
        routeName: 'organizational-structure.index',
        icon: BriefcaseBusiness,
        section: 'settings',
        activePattern: 'organizational-structure.*',
    },

    { name: 'Usuarios', routeName: 'users.index', icon: ShieldCheck, section: 'security', activePattern: 'users.*' },
    { name: 'Reportes', routeName: 'reports.index', icon: BarChart3, section: 'reports', activePattern: 'reports.*' },
];

const isActive = (item) => {
    return route().current(item.activePattern);
};

const showDivider = (index) => {
    if (index === 0) return false;

    return menuItems[index].section !== menuItems[index - 1].section;
};

const handleNavigate = () => {
    emit('close-mobile');
};
</script>

<template>
    <aside
        class="fixed left-0 top-0 z-50 flex h-screen flex-col bg-primary text-white shadow-xl transition-all duration-300"
        :class="[
            collapsed ? 'lg:w-20' : 'lg:w-64',
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            'w-72'
        ]">
        <div class="flex h-20 items-center justify-between border-b border-white/10 px-5"
            :class="collapsed ? 'lg:justify-center' : 'justify-between'">
            <ApplicationLogo :collapsed="collapsed" />

            <button type="button"
                class="rounded-lg p-2 text-white/80 transition hover:bg-white/15 hover:text-white lg:hidden"
                @click="emit('close-mobile')">
                <X class="h-5 w-5" />
            </button>
        </div>

        <nav class="sidebar-scroll mt-3 flex-1 overflow-y-auto px-3 pb-4">
            <template v-for="(item, index) in menuItems" :key="item.name">
                <div v-if="showDivider(index)" class="my-2 border-t border-white/10" />

                <Link :href="route(item.routeName)"
                    class="group relative flex items-center rounded-xl px-3 py-2.5 text-[13px] font-semibold transition-all duration-200"
                    :class="[
                        collapsed ? 'lg:justify-center' : 'gap-3',
                        isActive(item)
                            ? 'bg-white text-primary shadow-md'
                            : 'text-white/90 hover:bg-white/10'
                    ]" :title="collapsed ? item.name : ''" @click="handleNavigate">
                    <span v-if="isActive(item)"
                        class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-secondary" />

                    <component :is="item.icon" class="h-5 w-5 shrink-0" />

                    <span :class="collapsed ? 'lg:hidden' : ''">
                        {{ item.name }}
                    </span>
                </Link>
            </template>
        </nav>

        <div class="hidden border-t border-white/10 p-3 lg:block">
            <button type="button"
                class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-white text-primary shadow transition hover:scale-105 hover:bg-secondary hover:text-white"
                @click="emit('toggle')" :title="collapsed ? 'Mostrar menú' : 'Ocultar menú'">
                <ChevronRight v-if="collapsed" class="h-5 w-5" />
                <ChevronLeft v-else class="h-5 w-5" />
            </button>
        </div>
    </aside>
</template>
