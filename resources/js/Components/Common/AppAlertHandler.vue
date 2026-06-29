<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

import {
    showErrorAlert,
    showInfoAlert,
    showSuccessAlert,
    showWarningAlert,
} from '@/Utils/alerts';

const page = usePage();

/**
 * Mensajes flash enviados desde Laravel.
 *
 * Ejemplo:
 * return back()->with('success', 'Registro guardado correctamente.');
 */
const flash = computed(() => {
    return page.props.flash ?? {};
});

/**
 * Errores de validación enviados por Laravel/Inertia.
 *
 * Ejemplo:
 * throw ValidationException::withMessages([
 *     'attendance' => 'No puedes cerrar esta asistencia mensual...'
 * ]);
 */
const errors = computed(() => {
    return page.props.errors ?? {};
});

/**
 * Normaliza mensajes que pueden venir como string o array.
 */
const normalizeMessage = (message) => {
    if (!message) {
        return '';
    }

    if (Array.isArray(message)) {
        return message[0] ?? '';
    }

    return String(message);
};

/**
 * Muestra una alerta según su tipo.
 */
const showAlert = (type, message) => {
    const normalizedMessage = normalizeMessage(message);

    if (!normalizedMessage) {
        return;
    }

    if (type === 'success') {
        showSuccessAlert(normalizedMessage);
        return;
    }

    if (type === 'error') {
        showErrorAlert(normalizedMessage);
        return;
    }

    if (type === 'warning') {
        showWarningAlert(normalizedMessage);
        return;
    }

    if (type === 'info') {
        showInfoAlert(normalizedMessage);
    }
};

/**
 * Escucha mensajes flash.
 */
watch(
    () => flash.value,
    (messages) => {
        if (!messages) {
            return;
        }

        if (messages.success) {
            showAlert('success', messages.success);
        }

        if (messages.error) {
            showAlert('error', messages.error);
        }

        if (messages.warning) {
            showAlert('warning', messages.warning);
        }

        if (messages.info) {
            showAlert('info', messages.info);
        }

        if (messages.status) {
            showAlert('info', messages.status);
        }
    },
    {
        deep: true,
        immediate: true,
    },
);

/**
 * Escucha errores de validación.
 *
 * Esto permite mostrar errores como:
 * - No puedes cerrar porque el periodo todavía no terminó.
 * - No puedes cerrar porque existen días sin marcar.
 */
watch(
    () => errors.value,
    (validationErrors) => {
        if (!validationErrors || Object.keys(validationErrors).length === 0) {
            return;
        }

        const firstError = Object.values(validationErrors)[0];

        showAlert('error', firstError);
    },
    {
        deep: true,
        immediate: true,
    },
);
</script>

<template>
    <div class="hidden"></div>
</template>
