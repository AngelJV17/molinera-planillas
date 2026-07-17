<script setup>
import { computed, ref, watch } from 'vue';

import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SectionCard from '@/Components/Common/SectionCard.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        required: true,
    },
    initialDistrict: {
        type: Object,
        default: null,
    },
});

const catalogOptions = (type) => props.options.catalogs?.[type] ?? [];

const selectedDepartmentId = ref(props.initialDistrict?.province?.department?.id ?? '');
const selectedProvinceId = ref(props.initialDistrict?.province?.id ?? '');

const filteredProvinces = computed(() => props.options.provinces.filter((province) => (
    !selectedDepartmentId.value || Number(province.department_id) === Number(selectedDepartmentId.value)
)));

const filteredDistricts = computed(() => props.options.districts.filter((district) => (
    !selectedProvinceId.value || Number(district.province_id) === Number(selectedProvinceId.value)
)));

const selectedPensionSystem = computed(() => {
    return catalogOptions('PENSION_SYSTEM').find((item) => Number(item.id) === Number(props.form.pension_system_id));
});

const showCuspp = computed(() => {
    return selectedPensionSystem.value?.code?.startsWith('AFP');
});

const addBankAccount = () => {
    props.form.bank_accounts.push({
        bank_id: '',
        account_type_id: '',
        account_number: '',
        cci: '',
        is_primary: props.form.bank_accounts.length === 0,
        status: true,
    });
};

const removeBankAccount = (index) => {
    props.form.bank_accounts.splice(index, 1);

    if (props.form.bank_accounts.length && !props.form.bank_accounts.some((account) => account.is_primary)) {
        props.form.bank_accounts[0].is_primary = true;
    }
};

const markPrimaryBankAccount = (index) => {
    props.form.bank_accounts = props.form.bank_accounts.map((account, accountIndex) => ({
        ...account,
        is_primary: accountIndex === index,
    }));
};

watch(selectedDepartmentId, () => {
    selectedProvinceId.value = '';
    props.form.district_id = '';
});

watch(selectedProvinceId, () => {
    props.form.district_id = '';
});

watch(showCuspp, (value) => {
    if (!value) {
        props.form.cuspp = '';
    }
});

watch(
    () => props.form.has_system_access,
    (value) => {
        if (!value) {
            props.form.username = '';
            props.form.password = '';
        }
    },
);
</script>

<template>
    <div class="space-y-6">
        <SectionCard title="Identificación" description="Datos principales del trabajador.">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <InputLabel value="Código interno" />

                    <div
                        class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-gray-700">
                        {{ form.employee_code || 'Se generará automáticamente' }}
                    </div>

                    <p class="mt-2 text-xs text-gray-500">
                        Este código es asignado por el sistema y no debe modificarse manualmente.
                    </p>

                    <InputError class="mt-2" :message="form.errors.employee_code" />
                </div>

                <div>
                    <InputLabel for="document_type_id" value="Tipo de documento" required />
                    <select id="document_type_id" v-model="form.document_type_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('DOCUMENT_TYPE')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.document_type_id" />
                </div>

                <div>
                    <InputLabel for="document_number" value="Número de documento" required />
                    <TextInput id="document_number" v-model="form.document_number" class="mt-1 block w-full"
                        placeholder="Ej: 45871236" />
                    <InputError class="mt-2" :message="form.errors.document_number" />
                </div>

                <div>
                    <InputLabel for="birth_date" value="Fecha de nacimiento" optional />
                    <TextInput id="birth_date" v-model="form.birth_date" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.birth_date" />
                </div>
            </div>
        </SectionCard>

        <SectionCard title="Datos personales" description="Nombres, contacto y ubicación del trabajador.">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <InputLabel for="first_name" value="Nombres" required />
                    <TextInput id="first_name" v-model="form.first_name" class="mt-1 block w-full"
                        placeholder="Ej: Juan Carlos" />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div>
                    <InputLabel for="last_name" value="Apellidos" required />
                    <TextInput id="last_name" v-model="form.last_name" class="mt-1 block w-full"
                        placeholder="Ej: Pérez Huamán" />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>

                <div>
                    <InputLabel for="gender_id" value="Género" optional />
                    <select id="gender_id" v-model="form.gender_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('GENDER')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.gender_id" />
                </div>

                <div>
                    <InputLabel for="marital_status_id" value="Estado civil" optional />
                    <select id="marital_status_id" v-model="form.marital_status_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('MARITAL_STATUS')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.marital_status_id" />
                </div>

                <div>
                    <InputLabel for="phone" value="Teléfono" optional />
                    <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" placeholder="Ej: 987654321" />
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>

                <div>
                    <InputLabel for="email" value="Correo" optional />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full"
                        placeholder="correo@empresa.com" />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="address" value="Dirección" optional />
                    <TextInput id="address" v-model="form.address" class="mt-1 block w-full"
                        placeholder="Dirección del trabajador" />
                    <InputError class="mt-2" :message="form.errors.address" />
                </div>

                <div>
                    <InputLabel for="department_id" value="Departamento" optional />
                    <select id="department_id" v-model="selectedDepartmentId"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="department in options.departments" :key="department.id" :value="department.id">
                            {{ department.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <InputLabel for="province_id" value="Provincia" optional />
                    <select id="province_id" v-model="selectedProvinceId"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="province in filteredProvinces" :key="province.id" :value="province.id">
                            {{ province.name }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="district_id" value="Distrito" optional />
                    <select id="district_id" v-model="form.district_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="district in filteredDistricts" :key="district.id" :value="district.id">
                            {{ district.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.district_id" />
                </div>
            </div>
        </SectionCard>

        <SectionCard title="Datos laborales" description="Asignación, turno, remuneración y estado laboral.">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <InputLabel for="hire_date" value="Fecha de ingreso" required />
                    <TextInput id="hire_date" v-model="form.hire_date" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.hire_date" />
                </div>

                <div>
                    <InputLabel for="termination_date" value="Fecha de cese" optional />
                    <TextInput id="termination_date" v-model="form.termination_date" type="date"
                        class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.termination_date" />
                </div>

                <div>
                    <InputLabel for="position_id" value="Cargo" optional />
                    <select id="position_id" v-model="form.position_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('POSITION')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.position_id" />
                </div>

                <div>
                    <InputLabel for="work_area_id" value="Área" optional />
                    <select id="work_area_id" v-model="form.work_area_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('WORK_AREA')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.work_area_id" />
                </div>

                <div>
                    <InputLabel for="payroll_group_id" value="Grupo de planilla" optional />
                    <select id="payroll_group_id" v-model="form.payroll_group_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('PAYROLL_GROUP')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.payroll_group_id" />
                </div>

                <div>
                    <InputLabel for="work_shift_id" value="Turno" optional />
                    <select id="work_shift_id" v-model="form.work_shift_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="shift in options.workShifts" :key="shift.id" :value="shift.id">
                            {{ shift.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.work_shift_id" />
                </div>

                <div>
                    <InputLabel for="employment_status_id" value="Estado laboral" optional />
                    <select id="employment_status_id" v-model="form.employment_status_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('WORKER_STATUS')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.employment_status_id" />
                </div>

                <div>
                    <InputLabel for="base_salary" value="Sueldo básico" required />
                    <TextInput id="base_salary" v-model="form.base_salary" type="number" min="0" step="0.01"
                        class="mt-1 block w-full" placeholder="Ej: 1130.00" />
                    <InputError class="mt-2" :message="form.errors.base_salary" />
                </div>

                <div>
                    <InputLabel for="pension_system_id" value="Sistema pensionario" optional />
                    <select id="pension_system_id" v-model="form.pension_system_id"
                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('PENSION_SYSTEM')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.pension_system_id" />
                </div>

                <div v-if="showCuspp">
                    <InputLabel for="cuspp" value="CUSPP" optional />
                    <TextInput id="cuspp" v-model="form.cuspp" class="mt-1 block w-full"
                        placeholder="Código AFP si corresponde" />
                    <InputError class="mt-2" :message="form.errors.cuspp" />
                </div>

                <div class="flex items-end">
                    <label
                        class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input id="status" v-model="form.status" type="checkbox"
                            class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                        <span class="text-sm font-semibold text-gray-700">
                            Trabajador activo
                        </span>
                    </label>
                </div>
            </div>
        </SectionCard>

        <SectionCard title="Cuentas bancarias" description="Registra las cuentas usadas para pagos de planilla.">
            <div class="space-y-4">
                <div
                    v-for="(account, index) in form.bank_accounts"
                    :key="index"
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel :for="`bank_id_${index}`" value="Banco" optional />
                            <select
                                :id="`bank_id_${index}`"
                                v-model="account.bank_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            >
                                <option value="">Seleccionar</option>
                                <option v-for="bank in options.banks" :key="bank.id" :value="bank.id">
                                    {{ bank.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors[`bank_accounts.${index}.bank_id`]" />
                        </div>

                        <div>
                            <InputLabel :for="`account_type_id_${index}`" value="Tipo de cuenta" optional />
                            <select
                                :id="`account_type_id_${index}`"
                                v-model="account.account_type_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            >
                                <option value="">Seleccionar</option>
                                <option v-for="item in catalogOptions('ACCOUNT_TYPE')" :key="item.id" :value="item.id">
                                    {{ item.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors[`bank_accounts.${index}.account_type_id`]" />
                        </div>

                        <div>
                            <InputLabel :for="`account_number_${index}`" value="Numero de cuenta" optional />
                            <TextInput
                                :id="`account_number_${index}`"
                                v-model="account.account_number"
                                class="mt-1 block w-full"
                                placeholder="Numero de cuenta"
                            />
                            <InputError class="mt-2" :message="form.errors[`bank_accounts.${index}.account_number`]" />
                        </div>

                        <div>
                            <InputLabel :for="`cci_${index}`" value="CCI" optional />
                            <TextInput
                                :id="`cci_${index}`"
                                v-model="account.cci"
                                class="mt-1 block w-full"
                                placeholder="Codigo de cuenta interbancaria"
                            />
                            <InputError class="mt-2" :message="form.errors[`bank_accounts.${index}.cci`]" />
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-3">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <input
                                    type="radio"
                                    class="border-slate-300 text-primary focus:ring-primary"
                                    :checked="account.is_primary"
                                    @change="markPrimaryBankAccount(index)"
                                >
                                Cuenta principal
                            </label>

                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <input
                                    v-model="account.status"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-primary focus:ring-primary"
                                >
                                Activa
                            </label>
                        </div>

                        <button
                            type="button"
                            class="text-sm font-bold text-danger hover:text-red-700"
                            @click="removeBankAccount(index)"
                        >
                            Quitar cuenta
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-slate-50"
                    @click="addBankAccount"
                >
                    Agregar cuenta bancaria
                </button>
            </div>
        </SectionCard>

        <SectionCard title="Acceso al sistema" description="Define si este trabajador podrá ingresar al sistema.">
            <label class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                <div>
                    <p class="font-semibold text-gray-900">
                        Permitir acceso al sistema
                    </p>

                    <p class="mt-1 text-sm text-gray-500">
                        Si se activa esta opción, el sistema creará automáticamente una cuenta vinculada al trabajador.
                    </p>
                </div>

                <button type="button" class="relative h-7 w-14 rounded-full transition"
                    :class="form.has_system_access ? 'bg-primary' : 'bg-slate-300'"
                    @click="form.has_system_access = !form.has_system_access">
                    <span class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition"
                        :class="form.has_system_access ? 'left-8' : 'left-1'" />
                </button>
            </label>

            <div v-if="form.has_system_access" class="mt-4 rounded-xl border border-primary/20 bg-primary/5 p-4">
                <p class="text-sm text-gray-700">
                    Se creará automáticamente un usuario activo para este trabajador.
                </p>

                <ul class="mt-3 space-y-2 text-xs text-gray-600">
                    <li>• El usuario quedará vinculado al trabajador.</li>
                    <li>• Se generará una contraseña temporal automática.</li>
                    <li>• El acceso podrá administrarse posteriormente desde el módulo Usuarios.</li>
                </ul>
            </div>
        </SectionCard>
    </div>
</template>
