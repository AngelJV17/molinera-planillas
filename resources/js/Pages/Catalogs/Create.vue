<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Save } from 'lucide-vue-next';

const form = useForm({
    type: '',
    code: '',
    name: '',
    description: '',
    status: true,
});

/**
 * Envía el formulario al backend usando Inertia.
 */
const submit = () => {
    form.post(route('catalogs.store'));
};
</script>

<template>

    <Head title="Nuevo catálogo" />

    <AuthenticatedLayout title="Nuevo catálogo">
        <section class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        Registrar catálogo
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Crea una nueva opción reutilizable para el sistema.
                    </p>
                </div>

                <Link :href="route('catalogs.index')"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                    Volver
                </Link>
            </div>

            <form class="rounded-2xl border border-slate-200 bg-white p-6 shadow-md" @submit.prevent="submit">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-gray-700">
                            Tipo
                        </label>
                        <input v-model="form.type" type="text" placeholder="Ej: WORKER_STATUS"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                        <p v-if="form.errors.type" class="mt-1 text-sm text-danger">
                            {{ form.errors.type }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">
                            Código
                        </label>
                        <input v-model="form.code" type="text" placeholder="Ej: ACTIVE"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                        <p v-if="form.errors.code" class="mt-1 text-sm text-danger">
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">
                            Nombre
                        </label>
                        <input v-model="form.name" type="text" placeholder="Ej: Activo"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-danger">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">
                            Estado
                        </label>
                        <select v-model="form.status"
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary">
                            <option :value="true">Activo</option>
                            <option :value="false">Inactivo</option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-danger">
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-gray-700">
                            Descripción
                        </label>
                        <textarea v-model="form.description" rows="4" placeholder="Descripción opcional..."
                            class="mt-2 w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary"></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-danger">
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                        :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        Guardar catálogo
                    </button>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>
