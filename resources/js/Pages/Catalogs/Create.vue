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
    type: {
        type: String,
        required: true,
    },
});

/**
 * Obtiene la configuración visual y descriptiva de la categoría actual.
 */
const category = computed(() => {
    return getCatalogCategory(props.type);
});

const form = useForm({
    type: props.type,
    code: '',
    name: '',
    description: '',
    status: true,
});

/**
 * Guarda la nueva opción de catálogo.
 */
const submit = () => {
    form.post(route('catalogs.store'));
};
</script>

<template>

    <Head :title="`Nuevo ${category.label}`" />

    <AuthenticatedLayout :title="`Nuevo ${category.label}`">
        <section class="mx-auto max-w-3xl space-y-6">
            <PageHeader :title="`Registrar ${category.label}`"
                description="Crea una nueva opción reutilizable para los módulos del sistema.">
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

            <!-- Tarjeta informativa de la categoría actual -->
            <div class="rounded-3xl border border-primary/20 bg-white p-5 shadow-lg">
                <div class="flex gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <component :is="category.icon" class="h-7 w-7" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-primary">
                                Categoría seleccionada
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

                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
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
                </div>
            </div>

            <SectionCard :title="`Datos de ${category.label}`"
                :description="`Completa la información principal para registrar un nuevo ${category.label.toLowerCase()}.`">
                <form @submit.prevent="submit">
                    <Form :form="form" :category-label="category.label" />

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow transition hover:bg-primary-dark disabled:opacity-60"
                            :disabled="form.processing">
                            <Save class="h-4 w-4" />

                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </SectionCard>
        </section>
    </AuthenticatedLayout>
</template>
