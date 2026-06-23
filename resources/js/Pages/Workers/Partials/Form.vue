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

watch(selectedDepartmentId, () => {
    selectedProvinceId.value = '';
    props.form.district_id = '';
});

watch(selectedProvinceId, () => {
    props.form.district_id = '';
});
</script>

<template>
    <div class="space-y-6">
        <SectionCard title="Identificación" description="Datos principales del trabajador.">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <InputLabel for="employee_code" value="Código interno" />
                    <TextInput id="employee_code" v-model="form.employee_code" class="mt-1 block w-full" placeholder="Ej: EMP-001" />
                    <InputError class="mt-2" :message="form.errors.employee_code" />
                </div>

                <div>
                    <InputLabel for="document_type_id" value="Tipo de documento" />
                    <select id="document_type_id" v-model="form.document_type_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('DOCUMENT_TYPE')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.document_type_id" />
                </div>

                <div>
                    <InputLabel for="document_number" value="Número de documento" />
                    <TextInput id="document_number" v-model="form.document_number" class="mt-1 block w-full" placeholder="Ej: 45871236" />
                    <InputError class="mt-2" :message="form.errors.document_number" />
                </div>

                <div>
                    <InputLabel for="birth_date" value="Fecha de nacimiento" />
                    <TextInput id="birth_date" v-model="form.birth_date" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.birth_date" />
                </div>
            </div>
        </SectionCard>

        <SectionCard title="Datos personales" description="Nombres, contacto y ubicación del trabajador.">
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <InputLabel for="first_name" value="Nombres" />
                    <TextInput id="first_name" v-model="form.first_name" class="mt-1 block w-full" placeholder="Ej: Juan Carlos" />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div>
                    <InputLabel for="last_name" value="Apellidos" />
                    <TextInput id="last_name" v-model="form.last_name" class="mt-1 block w-full" placeholder="Ej: Pérez Huamán" />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>

                <div>
                    <InputLabel for="gender_id" value="Género" />
                    <select id="gender_id" v-model="form.gender_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('GENDER')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.gender_id" />
                </div>

                <div>
                    <InputLabel for="marital_status_id" value="Estado civil" />
                    <select id="marital_status_id" v-model="form.marital_status_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('MARITAL_STATUS')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.marital_status_id" />
                </div>

                <div>
                    <InputLabel for="phone" value="Teléfono" />
                    <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" placeholder="Ej: 987654321" />
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>

                <div>
                    <InputLabel for="email" value="Correo" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" placeholder="correo@empresa.com" />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="address" value="Dirección" />
                    <TextInput id="address" v-model="form.address" class="mt-1 block w-full" placeholder="Dirección del trabajador" />
                    <InputError class="mt-2" :message="form.errors.address" />
                </div>

                <div>
                    <InputLabel for="department_id" value="Departamento" />
                    <select id="department_id" v-model="selectedDepartmentId" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="department in options.departments" :key="department.id" :value="department.id">
                            {{ department.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <InputLabel for="province_id" value="Provincia" />
                    <select id="province_id" v-model="selectedProvinceId" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="province in filteredProvinces" :key="province.id" :value="province.id">
                            {{ province.name }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="district_id" value="Distrito" />
                    <select id="district_id" v-model="form.district_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
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
                    <InputLabel for="hire_date" value="Fecha de ingreso" />
                    <TextInput id="hire_date" v-model="form.hire_date" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.hire_date" />
                </div>

                <div>
                    <InputLabel for="termination_date" value="Fecha de cese" />
                    <TextInput id="termination_date" v-model="form.termination_date" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.termination_date" />
                </div>

                <div>
                    <InputLabel for="position_id" value="Cargo" />
                    <select id="position_id" v-model="form.position_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('POSITION')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.position_id" />
                </div>

                <div>
                    <InputLabel for="work_area_id" value="Área" />
                    <select id="work_area_id" v-model="form.work_area_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('WORK_AREA')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.work_area_id" />
                </div>

                <div>
                    <InputLabel for="work_shift_id" value="Turno" />
                    <select id="work_shift_id" v-model="form.work_shift_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="shift in options.workShifts" :key="shift.id" :value="shift.id">
                            {{ shift.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.work_shift_id" />
                </div>

                <div>
                    <InputLabel for="employment_status_id" value="Estado laboral" />
                    <select id="employment_status_id" v-model="form.employment_status_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('WORKER_STATUS')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.employment_status_id" />
                </div>

                <div>
                    <InputLabel for="base_salary" value="Sueldo básico" />
                    <TextInput id="base_salary" v-model="form.base_salary" type="number" min="0" step="0.01" class="mt-1 block w-full" placeholder="Ej: 1130.00" />
                    <InputError class="mt-2" :message="form.errors.base_salary" />
                </div>

                <div>
                    <InputLabel for="pension_system_id" value="Sistema pensionario" />
                    <select id="pension_system_id" v-model="form.pension_system_id" class="mt-1 block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Seleccionar</option>
                        <option v-for="item in catalogOptions('PENSION_SYSTEM')" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.pension_system_id" />
                </div>

                <div>
                    <InputLabel for="cuspp" value="CUSPP" />
                    <TextInput id="cuspp" v-model="form.cuspp" class="mt-1 block w-full" placeholder="Código AFP si corresponde" />
                    <InputError class="mt-2" :message="form.errors.cuspp" />
                </div>

                <div class="flex items-end">
                    <label class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <input id="status" v-model="form.status" type="checkbox" class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary">
                        <span class="text-sm font-semibold text-gray-700">Trabajador activo</span>
                    </label>
                </div>
            </div>
        </SectionCard>
    </div>
</template>
