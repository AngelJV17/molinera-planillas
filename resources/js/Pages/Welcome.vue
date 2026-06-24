<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    ArrowRight,
    BarChart3,
    CalendarCheck,
    CheckCircle2,
    Clock3,
    FileSpreadsheet,
    Leaf,
    LockKeyhole,
    Mail,
    MapPin,
    Menu,
    Phone,
    ReceiptText,
    ShieldCheck,
    Users,
    Wheat,
    X,
} from 'lucide-vue-next';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const activeSection = ref('inicio');
const mobileMenuOpen = ref(false);

const navItems = [
    { id: 'inicio', label: 'Inicio' },
    { id: 'modulos', label: 'Módulos' },
    { id: 'beneficios', label: 'Beneficios' },
    { id: 'nosotros', label: 'Nosotros' },
    { id: 'contacto', label: 'Contacto' },
];

const setActiveSection = () => {
    const sections = navItems
        .map((item) => document.getElementById(item.id))
        .filter(Boolean);

    const current = sections.find((section) => {
        const rect = section.getBoundingClientRect();
        return rect.top <= 120 && rect.bottom >= 120;
    });

    if (current) activeSection.value = current.id;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

onMounted(() => {
    setActiveSection();
    window.addEventListener('scroll', setActiveSection);
});

onUnmounted(() => {
    window.removeEventListener('scroll', setActiveSection);
});

const navClass = (id) => computed(() => (
    activeSection.value === id
        ? 'text-secondary after:w-full'
        : 'text-slate-700 after:w-0 hover:text-secondary hover:after:w-full'
));
</script>

<template>

    <Head title="MOLICENTE" />

    <div class="min-h-screen scroll-smooth bg-[#F8F5EC] text-slate-900">
        <!-- NAVBAR -->
        <header class="sticky top-0 z-50 border-b border-[#E8DEC8] bg-[#F8F5EC]/95 backdrop-blur-xl">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="#inicio" class="flex min-w-0 items-center gap-3" @click="closeMobileMenu">
                    <img src="/images/molicente-icon.png" alt="MOLICENTE"
                        class="h-12 w-auto rounded-xl bg-white p-2 shadow-md sm:h-14">

                    <div class="min-w-0">
                        <h1 class="font-rajdhani text-2xl font-black leading-none text-primary sm:text-3xl">
                            MOLICENTE
                        </h1>

                        <p class="hidden text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500 sm:block">
                            Industria Molinera San Vicente SRL
                        </p>
                    </div>
                </a>

                <nav class="hidden items-center gap-7 text-sm font-black lg:flex">
                    <a v-for="item in navItems" :key="item.id" :href="`#${item.id}`"
                        class="relative py-2 transition after:absolute after:bottom-0 after:left-0 after:h-0.5 after:bg-secondary after:transition-all"
                        :class="navClass(item.id).value">
                        {{ item.label }}
                    </a>
                </nav>

                <div class="flex items-center gap-2">
                    <nav v-if="canLogin" class="hidden items-center gap-3 sm:flex">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-black text-white shadow-lg transition hover:bg-primary-dark">
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link :href="route('login')"
                                class="rounded-xl border border-primary/30 bg-white px-4 py-2.5 text-sm font-black text-primary shadow-sm transition hover:bg-primary/10">
                                Iniciar sesión
                            </Link>
                        </template>
                    </nav>

                    <button type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-[#E8DEC8] bg-white text-primary shadow-sm lg:hidden"
                        @click="mobileMenuOpen = !mobileMenuOpen">
                        <X v-if="mobileMenuOpen" class="h-5 w-5" />
                        <Menu v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Menú móvil -->
            <div v-if="mobileMenuOpen" class="border-t border-[#E8DEC8] bg-white px-4 py-4 shadow-lg lg:hidden">
                <nav class="grid gap-2">
                    <a v-for="item in navItems" :key="item.id" :href="`#${item.id}`"
                        class="rounded-xl px-4 py-3 text-sm font-black transition"
                        :class="activeSection === item.id ? 'bg-primary text-white' : 'text-slate-700 hover:bg-slate-100'"
                        @click="closeMobileMenu">
                        {{ item.label }}
                    </a>

                    <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')"
                        class="mt-2 rounded-xl bg-secondary px-4 py-3 text-center text-sm font-black text-white">
                        Acceder al sistema
                    </Link>
                </nav>
            </div>
        </header>

        <main>
            <!-- HERO -->
            <section id="inicio" class="relative scroll-mt-24 overflow-hidden">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(202,138,4,0.12),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(21,87,36,0.14),transparent_38%)]">
                </div>

                <div
                    class="relative mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:py-12 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:px-8 lg:py-14">
                    <div class="relative z-10">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="h-px w-9 bg-secondary"></span>
                            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-secondary sm:text-xs">
                                Sistema de Gestión de Personal, Asistencia y Planillas
                            </span>
                        </div>

                        <h2 class="max-w-3xl text-4xl font-black leading-[1.02] text-primary sm:text-5xl lg:text-6xl">
                            Administra trabajadores, asistencia y planillas
                            <span class="text-secondary"> desde una sola plataforma.</span>
                        </h2>

                        <p class="mt-5 max-w-xl text-sm leading-relaxed text-slate-600 sm:text-base">
                            MOLICENTE centraliza la gestión laboral del molino, reduce tareas manuales
                            y organiza la información para planillas y boletas de pago.
                        </p>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="#modulos"
                                class="inline-flex items-center justify-center gap-3 rounded-xl bg-primary px-6 py-3 text-sm font-black text-white shadow-xl transition hover:-translate-y-0.5 hover:bg-primary-dark">
                                Conocer módulos
                                <ArrowRight class="h-4 w-4" />
                            </a>

                            <Link :href="route('login')"
                                class="inline-flex items-center justify-center gap-3 rounded-xl border border-primary/30 bg-white px-6 py-3 text-sm font-black text-primary shadow-sm transition hover:-translate-y-0.5 hover:bg-primary/10">
                                <LockKeyhole class="h-4 w-4" />
                                Acceder al sistema
                            </Link>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3 text-xs font-bold text-slate-600 sm:text-sm">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm">
                                <CheckCircle2 class="h-4 w-4 text-primary" />
                                Seguro
                            </span>

                            <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm">
                                <CheckCircle2 class="h-4 w-4 text-primary" />
                                Eficiente
                            </span>

                            <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 shadow-sm">
                                <CheckCircle2 class="h-4 w-4 text-primary" />
                                Confiable
                            </span>
                        </div>
                    </div>

                    <div class="relative min-h-[260px] sm:min-h-[340px] lg:min-h-[430px]">
                        <div
                            class="absolute inset-0 overflow-hidden rounded-[2rem] border-[6px] border-white bg-primary shadow-2xl sm:rounded-[2.5rem] lg:-right-10 lg:rounded-bl-[4rem] lg:rounded-tl-[10rem] lg:border-l-[8px] lg:border-secondary">
                            <img src="/images/hero-molino.jpg" alt="Molino MOLICENTE"
                                class="h-full w-full object-cover">

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-primary/35 via-transparent to-transparent">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- MÓDULOS -->
            <section id="modulos" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-14 sm:px-6 lg:px-8 lg:py-18">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-secondary">
                        Módulos del sistema
                    </p>

                    <h2 class="mt-3 text-3xl font-black leading-tight text-primary sm:text-4xl">
                        Todo lo necesario para administrar al personal del molino.
                    </h2>

                    <p class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                        Desde el registro del trabajador hasta el control de asistencia,
                        generación de planillas y emisión de boletas.
                    </p>
                </div>

                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        class="rounded-[1.75rem] border border-[#E8DEC8] bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F8F0DA] text-primary">
                            <Users class="h-7 w-7" />
                        </div>

                        <h3 class="mt-5 text-lg font-black text-slate-900">
                            Trabajadores
                        </h3>

                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Registro de datos personales, laborales y contractuales.
                        </p>
                    </div>

                    <div
                        class="rounded-[1.75rem] border border-[#E8DEC8] bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F8F0DA] text-primary">
                            <CalendarCheck class="h-7 w-7" />
                        </div>

                        <h3 class="mt-5 text-lg font-black text-slate-900">
                            Asistencia
                        </h3>

                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Control mensual de asistencia, faltas y horas extras.
                        </p>
                    </div>

                    <div
                        class="rounded-[1.75rem] border border-[#E8DEC8] bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F8F0DA] text-primary">
                            <FileSpreadsheet class="h-7 w-7" />
                        </div>

                        <h3 class="mt-5 text-lg font-black text-slate-900">
                            Planillas
                        </h3>

                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Generación, revisión y aprobación de planillas.
                        </p>
                    </div>

                    <div
                        class="rounded-[1.75rem] border border-[#E8DEC8] bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F8F0DA] text-primary">
                            <ReceiptText class="h-7 w-7" />
                        </div>

                        <h3 class="mt-5 text-lg font-black text-slate-900">
                            Boletas
                        </h3>

                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Emisión y descarga ordenada de boletas de pago.
                        </p>
                    </div>
                </div>
            </section>

            <!-- BENEFICIOS -->
            <section id="beneficios" class="scroll-mt-24 bg-white py-14 lg:py-18">
                <div
                    class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:px-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-secondary">
                            Beneficios
                        </p>

                        <h2 class="mt-3 text-3xl font-black leading-tight text-primary sm:text-4xl">
                            Más orden, menos trabajo manual y mejor control.
                        </h2>

                        <p class="mt-4 max-w-md text-sm leading-relaxed text-slate-600 sm:text-base">
                            Una solución pensada para mejorar la gestión administrativa
                            del personal y reducir errores en procesos repetitivos.
                        </p>
                    </div>

                    <div
                        class="grid overflow-hidden rounded-[2rem] border border-[#E8DEC8] bg-white shadow-xl sm:grid-cols-2 lg:grid-cols-3">
                        <div class="border-b border-[#E8DEC8] p-6 text-center sm:border-r">
                            <ShieldCheck class="mx-auto h-8 w-8 text-primary" />
                            <h3 class="mt-4 font-black">Seguridad</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Acceso controlado.
                            </p>
                        </div>

                        <div class="border-b border-[#E8DEC8] p-6 text-center lg:border-r">
                            <Clock3 class="mx-auto h-8 w-8 text-secondary" />
                            <h3 class="mt-4 font-black">Rapidez</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Procesos ágiles.
                            </p>
                        </div>

                        <div class="border-b border-[#E8DEC8] p-6 text-center sm:border-r lg:border-r-0">
                            <BarChart3 class="mx-auto h-8 w-8 text-primary" />
                            <h3 class="mt-4 font-black">Centralización</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Datos en un solo lugar.
                            </p>
                        </div>

                        <div class="border-b border-[#E8DEC8] p-6 text-center lg:border-b-0 lg:border-r">
                            <Leaf class="mx-auto h-8 w-8 text-primary" />
                            <h3 class="mt-4 font-black">Orden</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Mejor seguimiento.
                            </p>
                        </div>

                        <div class="border-b border-[#E8DEC8] p-6 text-center sm:border-b-0 sm:border-r">
                            <Users class="mx-auto h-8 w-8 text-primary" />
                            <h3 class="mt-4 font-black">Escalable</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Crece con la empresa.
                            </p>
                        </div>

                        <div class="p-6 text-center">
                            <CheckCircle2 class="mx-auto h-8 w-8 text-primary" />
                            <h3 class="mt-4 font-black">Especializado</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Para gestión molinera.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- NOSOTROS -->
            <section id="nosotros" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-14 sm:px-6 lg:px-8 lg:py-18">
                <div
                    class="grid overflow-hidden rounded-[2rem] border border-[#E8DEC8] bg-white shadow-xl lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="bg-primary p-8 text-white sm:p-10">
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-secondary">
                            Sobre nosotros
                        </p>

                        <h2 class="mt-4 text-3xl font-black leading-tight sm:text-4xl">
                            Tradición molinera, visión de futuro.
                        </h2>

                        <p class="mt-4 text-sm leading-relaxed text-white/75 sm:text-base">
                            Industria Molinera San Vicente SRL combina experiencia,
                            compromiso y tecnología para mejorar su gestión administrativa.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-3">
                        <div class="border-b border-[#E8DEC8] p-6 text-center sm:border-b-0 sm:border-r">
                            <Wheat class="mx-auto h-9 w-9 text-secondary" />
                            <h3 class="mt-4 font-black">Calidad</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Procesos confiables.
                            </p>
                        </div>

                        <div class="border-b border-[#E8DEC8] p-6 text-center sm:border-b-0 sm:border-r">
                            <Users class="mx-auto h-9 w-9 text-primary" />
                            <h3 class="mt-4 font-black">Compromiso</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Equipo humano.
                            </p>
                        </div>

                        <div class="p-6 text-center">
                            <Leaf class="mx-auto h-9 w-9 text-primary" />
                            <h3 class="mt-4 font-black">Mejora continua</h3>
                            <p class="mt-2 text-sm text-slate-500">
                                Visión de futuro.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
                <div
                    class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-primary via-primary to-primary-dark p-8 text-white shadow-2xl sm:p-10">
                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-3xl font-black sm:text-4xl">
                                Acceso privado para usuarios autorizados.
                            </h2>

                            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/75 sm:text-base">
                                Ingresa al sistema para gestionar trabajadores, asistencia,
                                planillas y boletas de pago.
                            </p>
                        </div>

                        <Link :href="route('login')"
                            class="inline-flex items-center justify-center gap-3 rounded-xl bg-secondary px-7 py-3 text-sm font-black text-white shadow-xl transition hover:-translate-y-0.5">
                            Iniciar sesión
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- FOOTER -->
        <footer id="contacto" class="scroll-mt-24 bg-primary text-white">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm sm:px-6 lg:grid-cols-[1.2fr_0.7fr_0.7fr_1fr] lg:px-8">
                <div>
                    <div class="flex items-center gap-3">
                        <img src="/images/molicente-icon.png" alt="MOLICENTE"
                            class="h-12 w-auto rounded-xl bg-white p-2 shadow">

                        <div>
                            <p class="font-rajdhani text-2xl font-black">
                                MOLICENTE
                            </p>

                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/60">
                                Industria Molinera San Vicente SRL
                            </p>
                        </div>
                    </div>

                    <p class="mt-5 max-w-md leading-relaxed text-white/70">
                        Sistema de gestión de asistencia, planillas y boletas de pago.
                    </p>
                </div>

                <div>
                    <h3 class="font-black">Navegación</h3>
                    <div class="mt-4 grid gap-2 text-white/70">
                        <a href="#inicio" class="hover:text-secondary">Inicio</a>
                        <a href="#modulos" class="hover:text-secondary">Módulos</a>
                        <a href="#beneficios" class="hover:text-secondary">Beneficios</a>
                        <a href="#nosotros" class="hover:text-secondary">Nosotros</a>
                    </div>
                </div>

                <div>
                    <h3 class="font-black">Módulos</h3>
                    <div class="mt-4 grid gap-2 text-white/70">
                        <p>Trabajadores</p>
                        <p>Asistencia</p>
                        <p>Planillas</p>
                        <p>Boletas</p>
                    </div>
                </div>

                <div>
                    <h3 class="font-black">Contacto</h3>

                    <div class="mt-4 grid gap-3 text-white/70">
                        <p class="flex items-center gap-2">
                            <MapPin class="h-4 w-4 text-secondary" />
                            Huánuco - Perú
                        </p>

                        <p class="flex items-center gap-2">
                            <Phone class="h-4 w-4 text-secondary" />
                            +51 913 123 456
                        </p>

                        <p class="flex items-center gap-2">
                            <Mail class="h-4 w-4 text-secondary" />
                            administracion@molicente.com
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10 px-5 py-5 text-center text-xs font-medium text-white/60">
                © 2026 Industria Molinera San Vicente SRL. Todos los derechos reservados.
            </div>
        </footer>
    </div>
</template>
