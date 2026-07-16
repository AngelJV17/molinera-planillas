<script setup>
import { usePage } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
});

defineEmits(['open-mobile-sidebar']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userName = computed(() => user.value?.name ?? 'Usuario');
const userRoles = computed(() => {
    const roles = user.value?.roles ?? [];

    return roles
        .map((role) => (typeof role === 'string' ? role : role?.name))
        .filter(Boolean);
});
const userLabel = computed(() => {
    return user.value?.display_label
        || user.value?.employee?.position
        || userRoles.value.join(', ')
        || 'Usuario sin rol';
});
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white shadow-sm">
        <div class="flex h-16 items-center justify-between px-4 sm:h-20 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <!-- Botón menú móvil -->
                <button type="button"
                    class="rounded-xl border border-gray-200 p-2 text-gray-600 transition hover:bg-gray-100 lg:hidden"
                    @click="$emit('open-mobile-sidebar')">
                    <Menu class="h-5 w-5" />
                </button>

                <div>
                    <h2 class="text-lg font-bold text-gray-800 sm:text-2xl">
                        {{ title }}
                    </h2>
                    <p class="hidden text-sm text-gray-500 sm:block">
                        Industria Molinera San Vicente SRL
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden text-right md:block">
                    <p class="text-sm font-semibold text-gray-700">
                        {{ userName }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ userLabel }}
                    </p>
                </div>

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-sm font-bold text-white shadow-sm transition hover:bg-primary-dark sm:h-11 sm:w-11">
                            {{ userInitial }}
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('profile.edit')">
                            Perfil
                        </DropdownLink>

                        <DropdownLink :href="route('logout')" method="post" as="button">
                            Cerrar sesión
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </div>
    </header>
</template>
