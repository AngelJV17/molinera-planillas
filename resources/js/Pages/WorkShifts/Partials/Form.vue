<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    form: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <InputLabel for="name" value="Nombre del turno" />
            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" placeholder="Ej: Turno administrativo" />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="daily_hours" value="Horas base del turno" />
            <TextInput id="daily_hours" v-model="form.daily_hours" type="number" min="0.5" max="24" step="0.25"
                class="mt-1 block w-full" placeholder="Ej: 8" />
            <InputError class="mt-2" :message="form.errors.daily_hours" />
        </div>

        <div>
            <InputLabel for="start_time" value="Hora de entrada base" />
            <TextInput id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="form.errors.start_time" />
        </div>

        <div>
            <InputLabel for="end_time" value="Hora de salida base" />
            <TextInput id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="form.errors.end_time" />
        </div>

        <div>
            <InputLabel for="break_start_time" value="Inicio de descanso base" />
            <TextInput id="break_start_time" v-model="form.break_start_time" type="time" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="form.errors.break_start_time" />
        </div>

        <div>
            <InputLabel for="break_end_time" value="Fin de descanso base" />
            <TextInput id="break_end_time" v-model="form.break_end_time" type="time" class="mt-1 block w-full" />
            <InputError class="mt-2" :message="form.errors.break_end_time" />
        </div>

        <div>
            <InputLabel for="tolerance_minutes" value="Minutos de tolerancia" />
            <TextInput id="tolerance_minutes" v-model="form.tolerance_minutes" type="number" min="0" max="240" step="1"
                class="mt-1 block w-full" placeholder="Ej: 10" />
            <InputError class="mt-2" :message="form.errors.tolerance_minutes" />
        </div>

        <div class="space-y-3">
            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input id="uses_daily_rules" v-model="form.uses_daily_rules" type="checkbox"
                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                <span class="text-sm font-semibold text-gray-700">Usar reglas por dia</span>
            </label>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input id="crosses_midnight" v-model="form.crosses_midnight" type="checkbox"
                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                <span class="text-sm font-semibold text-gray-700">Termina al dia siguiente</span>
            </label>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input id="rotation_enabled" v-model="form.rotation_enabled" type="checkbox"
                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                <span class="text-sm font-semibold text-gray-700">Descanso rotativo</span>
            </label>

            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <input id="status" v-model="form.status" type="checkbox"
                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                <span class="text-sm font-semibold text-gray-700">Turno activo</span>
            </label>

            <InputError :message="form.errors.uses_daily_rules" />
            <InputError :message="form.errors.crosses_midnight" />
            <InputError :message="form.errors.rotation_enabled" />
            <InputError :message="form.errors.status" />
        </div>

        <div v-if="form.rotation_enabled" class="grid gap-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 md:col-span-2 md:grid-cols-3">
            <div>
                <InputLabel for="rotation_work_days" value="Dias de trabajo" />
                <TextInput id="rotation_work_days" v-model="form.rotation_work_days" type="number" min="1" max="31"
                    class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.rotation_work_days" />
            </div>

            <div>
                <InputLabel for="rotation_rest_days" value="Dias de descanso" />
                <TextInput id="rotation_rest_days" v-model="form.rotation_rest_days" type="number" min="1" max="31"
                    class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.rotation_rest_days" />
            </div>

            <div>
                <InputLabel for="rotation_start_date" value="Inicio del ciclo" />
                <TextInput id="rotation_start_date" v-model="form.rotation_start_date" type="date"
                    class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.rotation_start_date" />
            </div>

            <p class="text-sm font-semibold text-blue-800 md:col-span-3">
                Ejemplo: trabaja {{ form.rotation_work_days || 6 }} noches y descansa {{ form.rotation_rest_days || 1 }}.
                La fecha de inicio debe ser el primer dia trabajado del ciclo.
            </p>
        </div>

        <div class="md:col-span-2">
            <InputLabel for="description" value="Descripcion" />
            <textarea id="description" v-model="form.description" rows="4" placeholder="Detalle opcional del turno..."
                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"></textarea>
            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div v-if="form.uses_daily_rules || form.rotation_enabled" class="md:col-span-2">
            <div class="mb-3">
                <h3 class="text-sm font-black uppercase tracking-wide text-gray-700">Reglas por dia</h3>
                <p class="text-sm text-gray-500">Define si el dia se trabaja, sus horas esperadas y si las horas extra se remuneran.</p>
            </div>

            <div class="space-y-3">
                <div v-for="(rule, index) in form.daily_rules" :key="rule.day_of_week"
                    class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="grid gap-3 lg:grid-cols-[110px_repeat(5,minmax(0,1fr))]">
                        <div class="flex items-center gap-2">
                            <input v-model="rule.is_working_day" type="checkbox"
                                class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                            <span class="text-sm font-black text-gray-800">{{ rule.label }}</span>
                        </div>

                        <div>
                            <InputLabel :for="`rule-start-${index}`" value="Entrada" />
                            <TextInput :id="`rule-start-${index}`" v-model="rule.start_time" type="time"
                                class="mt-1 block w-full" :disabled="!rule.is_working_day" />
                        </div>

                        <div>
                            <InputLabel :for="`rule-end-${index}`" value="Salida" />
                            <TextInput :id="`rule-end-${index}`" v-model="rule.end_time" type="time"
                                class="mt-1 block w-full" :disabled="!rule.is_working_day" />
                        </div>

                        <div>
                            <InputLabel :for="`rule-hours-${index}`" value="Horas esperadas" />
                            <TextInput :id="`rule-hours-${index}`" v-model="rule.expected_hours" type="number"
                                min="0" max="24" step="0.25" class="mt-1 block w-full" />
                        </div>

                        <div>
                            <InputLabel :for="`rule-overtime-after-${index}`" value="Extra desde" />
                            <TextInput :id="`rule-overtime-after-${index}`" v-model="rule.overtime_after_hours"
                                type="number" min="0" max="24" step="0.25" class="mt-1 block w-full"
                                placeholder="Horas" />
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-xs font-bold text-gray-700">
                                <input v-model="rule.overtime_pay_enabled" type="checkbox"
                                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                                Paga H. extra
                            </label>

                            <label class="flex items-center gap-2 text-xs font-bold text-gray-700">
                                <input v-model="rule.crosses_midnight" type="checkbox"
                                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                                Cruza medianoche
                            </label>

                            <label class="flex items-center gap-2 text-xs font-bold text-gray-700">
                                <input v-model="rule.counts_as_full_day" type="checkbox"
                                    class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                                Cuenta dia completo
                            </label>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div>
                            <InputLabel :for="`rule-break-start-${index}`" value="Inicio descanso" />
                            <TextInput :id="`rule-break-start-${index}`" v-model="rule.break_start_time" type="time"
                                class="mt-1 block w-full" :disabled="!rule.is_working_day" />
                        </div>

                        <div>
                            <InputLabel :for="`rule-break-end-${index}`" value="Fin descanso" />
                            <TextInput :id="`rule-break-end-${index}`" v-model="rule.break_end_time" type="time"
                                class="mt-1 block w-full" :disabled="!rule.is_working_day" />
                        </div>

                        <div>
                            <InputLabel :for="`rule-tolerance-${index}`" value="Tolerancia" />
                            <TextInput :id="`rule-tolerance-${index}`" v-model="rule.tolerance_minutes" type="number"
                                min="0" max="240" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
