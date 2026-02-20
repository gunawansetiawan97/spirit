<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, BaseFormRow, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseCheckbox } from '@/Components/Form';
import { useFormPage } from '@/Composables';

const props = withDefaults(defineProps<{
    id?: string | number;
    mode: 'create' | 'edit' | 'view';
}>(), { mode: 'create' });

const emit = defineEmits<{ (e: 'navigate', route: string): void }>();

const {
    loading, saving, formErrors, activeTab, auditData, recordId,
    formTabs, pageTitle, setupPage, fetchData, handleSubmit, handleBack, handleEdit,
} = useFormPage({
    title: 'Gudang',
    apiEndpoint: '/api/warehouses',
    basePath: '/master/warehouse',
    auditType: 'warehouse',
    auditRelations: ['createdBy', 'updatedBy'],
}, props, emit);

const form = ref({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const autoCode = ref(false);

const generateAutoCode = () => {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    const ts = `${now.getFullYear()}/${pad(now.getMonth() + 1)}/${pad(now.getDate())}/${pad(now.getHours())}/${pad(now.getMinutes())}/${pad(now.getSeconds())}`;
    return `AUTOCODE/${ts}/${Math.floor(Math.random() * 100000)}`;
};

onMounted(() => {
    setupPage();
    fetchData((data) => {
        form.value = {
            code: data.code,
            name: data.name,
            description: data.description || '',
            is_active: data.is_active,
        };
    });

    if (props.mode === 'create') {
        form.value.code = generateAutoCode();
        autoCode.value = true;
    }
});

const onSubmit = () => handleSubmit(() => ({
    code: autoCode.value ? undefined : form.value.code,
    name: form.value.name,
    description: form.value.description,
    is_active: form.value.is_active,
}));
</script>

<template>
    <FormPage
        :title="pageTitle"
        :mode="mode"
        :loading="loading"
        :saving="saving"
        :tabs="formTabs"
        v-model:active-tab="activeTab"
        @submit="onSubmit"
        @back="handleBack"
        @edit="handleEdit"
    >
        <template #default="{ readonly }">
            <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                <BaseFormRow label="Kode" :error="formErrors.code" :help-text="autoCode ? 'Nomor otomatis' : ''">
                    <BaseInput v-model="form.code" :disabled="readonly || autoCode || mode === 'edit'" />
                </BaseFormRow>

                <BaseFormRow label="Nama" required :error="formErrors.name">
                    <BaseInput v-model="form.name" placeholder="Nama gudang" :disabled="readonly" />
                </BaseFormRow>

                <BaseFormRow label="Status">
                    <div class="flex h-[34px] items-center">
                        <BaseCheckbox v-model="form.is_active" label="Aktif" :disabled="readonly" />
                    </div>
                </BaseFormRow>

                <BaseFormRow label="Deskripsi" class="col-span-2" :error="formErrors.description">
                    <BaseTextarea v-model="form.description" placeholder="Keterangan tambahan" :disabled="readonly" :rows="2" />
                </BaseFormRow>
            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="warehouse"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
