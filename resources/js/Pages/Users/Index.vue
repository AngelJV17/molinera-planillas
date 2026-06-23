<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { Plus, ShieldCheck, UserRound } from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import DataTable from '@/Components/Table/DataTable.vue';

const userForm = reactive({
    nombre: '',
    correo: '',
    rol: 'Asistente RRHH',
});

const users = ref([
    { id: 1, nombre: 'Administrador MOLICENTE', correo: 'admin@molicente.test', rol: 'Administrador', estado: 'Activo' },
    { id: 2, nombre: 'Contador General', correo: 'contador@molicente.test', rol: 'Contador', estado: 'Activo' },
    { id: 3, nombre: 'Asistente RRHH', correo: 'rrhh@molicente.test', rol: 'Asistente RRHH', estado: 'Pendiente' },
]);

const columns = [
    { key: 'nombre', label: 'Usuario' },
    { key: 'correo', label: 'Correo' },
    { key: 'rol', label: 'Rol' },
    { key: 'estado', label: 'Estado' },
];

const addUser = () => {
    users.value.unshift({
        id: Date.now(),
        nombre: userForm.nombre,
        correo: userForm.correo,
        rol: userForm.rol,
        estado: 'Pendiente',
    });
    userForm.nombre = '';
    userForm.correo = '';
    userForm.rol = 'Asistente RRHH';
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout title="Usuarios">
        <section class="space-y-6">
            <PageHeader title="Usuarios y permisos" description="Administra accesos simulados para gerencia, contador y RRHH.">
                <template #icon>
                    <ShieldCheck class="h-7 w-7" />
                </template>
            </PageHeader>

            <SectionCard title="Nuevo usuario" description="Registra un acceso preliminar para el sistema.">
                <form class="grid gap-4 lg:grid-cols-[1fr_1fr_220px_auto]" @submit.prevent="addUser">
                    <input v-model="userForm.nombre" required type="text" placeholder="Nombre" class="rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    <input v-model="userForm.correo" required type="email" placeholder="Correo" class="rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                    <select v-model="userForm.rol" class="rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary">
                        <option>Administrador</option>
                        <option>Contador</option>
                        <option>Asistente RRHH</option>
                        <option>Gerencia</option>
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white hover:bg-primary-dark">
                        <Plus class="h-4 w-4" />
                        Agregar
                    </button>
                </form>
            </SectionCard>

            <section class="space-y-4">
                <div>
                    <h2 class="text-lg font-black text-gray-900">Usuarios registrados</h2>
                    <p class="text-sm text-gray-500">Cuentas disponibles y roles asignados.</p>
                </div>

                <DataTable :columns="columns">
                    <tr v-for="user in users" :key="user.id" class="text-sm transition hover:bg-primary/5">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <UserRound class="h-5 w-5" />
                                </div>
                                <span class="font-bold text-gray-800">{{ user.nombre }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ user.correo }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-700">{{ user.rol }}</td>
                        <td class="px-6 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-bold" :class="user.estado === 'Activo' ? 'bg-primary/15 text-primary' : 'bg-secondary/15 text-secondary'">
                                {{ user.estado }}
                            </span>
                        </td>
                    </tr>
                </DataTable>
            </section>
        </section>
    </AuthenticatedLayout>
</template>
