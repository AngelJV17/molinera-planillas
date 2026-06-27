<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

import {
    ArrowLeft,
    Info,
    ListChecks,
    Save,
} from 'lucide-vue-next';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Common/PageHeader.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import Form from './Partials/Form.vue';

import { getCatalogCategory } from '@/Config/catalogCategories';

const props = defineProps({
    catalog: {
        type: Object,
        required: true,
    },
});

/**
 * Obtiene la configuración visual y descriptiva
 * según el tipo del catálogo editado.
 */
const category = computed(() => {
    return getCatalogCategory(props.catalog.type);
});

/**
 * Formulario de edición del catálogo.
 *
 * type:
 * Se conserva para saber a qué categoría pertenece el registro.
 */
const form = useForm({
    type: props.catalog.type,
    code: props.catalog.code,
    name: props.catalog.name,
    description: props.catalog.description ?? '',
    status: props.catalog.status,
});

/**
 * Actualiza la opción del catálogo seleccionada.
 */
const submit = () => {
    form.put(route('catalogs.update', props.catalog.id));
};
</script>

<template>

    <Head :title="`Editar ${category.label}`" />

    <AuthenticatedLayout :title="`Editar ${category.label}`">
        <section class="mx-auto max-w-3xl space-y-6">
            <PageHeader :title="`Editar ${category.label}`"
                description="Actualiza la información del registro seleccionado.">
                <template #icon>
                    <ListChecks class="h-7 w-7" />
                </template>

                <template #actions>
                    <Link :href="route('catalogs.index', { type: form.type })"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-slate-50">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </template>
            </PageHeader>

            <!-- Tarjeta informativa de la categoría del registro -->
            <div class="rounded-3xl border border-primary/20 bg-white p-5 shadow-lg">
                <div class="flex gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <component :is="category.icon" class="h-7 w-7" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-primary">
                                Categoría del registro
                            </p>

                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-600">
                                {{ form.type }}
                            </span>
                        </div>

                        <h2 class="mt-2 text-xl font-black text-gray-900">
                            {{ category.plural }}
                        </h2>

                        <p class="mt-1 text-sm leading-relaxed text-gray-500">
                            {{ category.description }}
                        </p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <!-- Registro actual -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-black uppercase tracking-wide text-gray-500">
                                    Registro actual
                                </p>

                                <p class="mt-1 text-sm font-bold text-gray-800">
                                    {{ form.name || 'Sin nombre registrado' }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-primary">
                                    Código: {{ form.code || 'Sin código' }}
                                </p>
                            </div>

                            <!-- Ejemplos de uso -->
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <Info class="mt-0.5 h-5 w-5 shrink-0 text-primary" />

                                    <div>
                                        <p class="text-xs font-black uppercase tracking-wide text-gray-500">
                                            Ejemplos de uso
                                        </p>

                                        <p class="mt-1 text-sm font-semibold text-gray-700">
                                            {{ category.examples }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 text-xs font-semibold leading-relaxed text-amber-700">
                            Verifica que el código y el nombre correspondan a esta categoría antes de guardar los
                            cambios.
                        </p>
                    </div>
                </div>
            </div>

            <SectionCard :title="`Datos de ${category.label}`"
                :description="`Revisa los campos antes de actualizar este ${category.label.toLowerCase()}.`">
                <form @submit.prevent="submit">
                    <Form :form="form" :category-label="category.label" />

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing">
                            <Save class="h-4 w-4" />

                            {{ form.processing ? 'Actualizando...' : 'Actualizar' }}
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
