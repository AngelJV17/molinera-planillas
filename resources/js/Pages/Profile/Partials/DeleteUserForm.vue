<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, LockKeyhole, Trash2 } from 'lucide-vue-next';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

/**
 * Abre el modal de confirmación y enfoca el campo contraseña.
 */
const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

/**
 * Elimina la cuenta del usuario autenticado.
 * Se solicita contraseña para evitar eliminaciones accidentales.
 */
const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="overflow-hidden rounded-3xl border border-danger/30 bg-white shadow-lg">
        <div class="border-b border-danger/20 bg-danger/10 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-danger/15 text-danger">
                    <AlertTriangle class="h-5 w-5" />
                </div>

                <div>
                    <h2 class="text-sm font-black uppercase tracking-wide text-danger">
                        Zona peligrosa
                    </h2>

                    <p class="text-xs text-gray-600">
                        Acciones irreversibles de la cuenta.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-5 p-6">
            <div>
                <h3 class="text-lg font-black text-gray-900">
                    Eliminar cuenta
                </h3>

                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                    Al eliminar tu cuenta, todos sus recursos y datos asociados serán eliminados permanentemente.
                    Esta acción no se puede deshacer.
                </p>
            </div>

            <DangerButton
                class="inline-flex items-center gap-2 rounded-xl bg-danger px-5 py-2.5 text-sm font-bold text-white shadow transition hover:opacity-90"
                @click="confirmUserDeletion">
                <Trash2 class="h-4 w-4" />
                Eliminar cuenta
            </DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-danger/10 text-danger">
                        <AlertTriangle class="h-6 w-6" />
                    </div>

                    <div>
                        <h2 class="text-xl font-black text-gray-900">
                            ¿Seguro que deseas eliminar tu cuenta?
                        </h2>

                        <p class="mt-2 text-sm leading-relaxed text-gray-600">
                            Esta acción eliminará permanentemente tu cuenta. Ingresa tu contraseña para confirmar.
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <InputLabel for="password" value="Contraseña" />

                    <div class="relative mt-2">
                        <LockKeyhole class="absolute left-3 top-3 h-5 w-5 text-gray-400" />

                        <TextInput id="password" ref="passwordInput" v-model="form.password" type="password"
                            class="block w-full rounded-xl border-slate-300 pl-11 text-sm shadow-sm focus:border-danger focus:ring-danger"
                            placeholder="••••••••" @keyup.enter="deleteUser" />
                    </div>

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-slate-50"
                        @click="closeModal">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        class="inline-flex items-center gap-2 rounded-xl bg-danger px-5 py-2.5 text-sm font-bold text-white shadow transition hover:opacity-90"
                        :class="{ 'opacity-60': form.processing }" :disabled="form.processing" @click="deleteUser">
                        <Trash2 class="h-4 w-4" />
                        Eliminar definitivamente
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
