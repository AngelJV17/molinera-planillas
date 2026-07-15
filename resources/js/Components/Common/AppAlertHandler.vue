<script setup>
import { usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import {
    showInfoAlert,
    showSuccessAlert,
    showWarningAlert,
} from '@/Utils/alerts';

const page = usePage();
const visibleErrorMessages = ref([]);

const flash = computed(() => {
    return page.props.flash ?? {};
});

const errors = computed(() => {
    return page.props.errors ?? {};
});

const normalizeMessage = (message) => {
    if (!message) {
        return '';
    }

    if (Array.isArray(message)) {
        return message[0] ?? '';
    }

    return String(message);
};

const normalizeMessages = (messages) => {
    return Object.values(messages ?? {})
        .flatMap((message) => (Array.isArray(message) ? message : [message]))
        .map(normalizeMessage)
        .filter(Boolean)
        .filter((message, index, allMessages) => allMessages.indexOf(message) === index);
};

const showFlashAlert = (type, message) => {
    const normalizedMessage = normalizeMessage(message);

    if (!normalizedMessage) {
        return;
    }

    const flashKey = `${type}:${normalizedMessage}`;
    const historyState = window.history.state ?? {};
    const shownFlashKeys = new Set(historyState.shownFlashKeys ?? []);

    if (shownFlashKeys.has(flashKey)) {
        return;
    }

    shownFlashKeys.add(flashKey);
    window.history.replaceState(
        {
            ...historyState,
            shownFlashKeys: [...shownFlashKeys].slice(-50),
        },
        '',
    );

    if (type === 'success') {
        showSuccessAlert(normalizedMessage);
        return;
    }

    if (type === 'error') {
        visibleErrorMessages.value = [normalizedMessage];
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

watch(
    () => flash.value,
    (messages) => {
        if (!messages) {
            return;
        }

        if (messages.success) {
            showFlashAlert('success', messages.success);
        }

        if (messages.error) {
            showFlashAlert('error', messages.error);
        }

        if (messages.warning) {
            showFlashAlert('warning', messages.warning);
        }

        if (messages.info) {
            showFlashAlert('info', messages.info);
        }

        if (messages.status) {
            showFlashAlert('info', messages.status);
        }
    },
    {
        deep: true,
        immediate: true,
    },
);

watch(
    () => errors.value,
    (validationErrors) => {
        if (!validationErrors || Object.keys(validationErrors).length === 0) {
            visibleErrorMessages.value = [];
            return;
        }

        visibleErrorMessages.value = normalizeMessages(validationErrors);
    },
    {
        deep: true,
        immediate: true,
    },
);

const dismissErrors = () => {
    visibleErrorMessages.value = [];
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visibleErrorMessages.length"
            class="fixed inset-x-0 top-4 z-[80] mx-auto w-[calc(100%-2rem)] max-w-3xl px-0 sm:top-6"
            role="alert"
            aria-live="assertive"
        >
            <div class="rounded-t border border-red-600 bg-red-600 px-4 py-2 text-white shadow-lg">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-bold">
                        No pudimos completar la accion
                    </p>

                    <button
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-white/80"
                        title="Cerrar alerta"
                        aria-label="Cerrar alerta"
                        @click="dismissErrors"
                    >
                        <X class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <div class="rounded-b border border-t-0 border-red-400 bg-red-50 px-4 py-3 text-red-800 shadow-lg">
                <p
                    v-for="message in visibleErrorMessages.slice(0, 3)"
                    :key="message"
                    class="text-sm font-semibold leading-relaxed"
                >
                    {{ message }}
                </p>

                <p
                    v-if="visibleErrorMessages.length > 3"
                    class="mt-2 text-xs font-semibold text-red-700"
                >
                    Hay {{ visibleErrorMessages.length - 3 }} error(es) adicional(es). Revisa los campos marcados en el formulario.
                </p>
            </div>
        </div>
    </Teleport>
</template>
