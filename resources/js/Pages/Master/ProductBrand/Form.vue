<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage, AuditInfo } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseCheckbox } from '@/Components/Form';
import { useFormPage } from '@/Composables';

const props = withDefaults(defineProps<{
    id?: string | number;
    mode: 'create' | 'edit' | 'view';
}>(), { mode: 'create' });

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const {
    loading, saving, formErrors, activeTab, auditData, recordId,
    formTabs, pageTitle, setupPage, fetchData, handleSubmit, handleBack, handleEdit,
} = useFormPage({
    title: 'Merk Produk',
    apiEndpoint: '/api/product-brands',
    basePath: '/master/product-brand',
    auditType: 'product-brand',
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
    const rand = Math.floor(Math.random() * 100000);
    return `AUTOCODE/${ts}/${rand}`;
};

onMounted(async () => {
    setupPage();
    await fetchData((data) => {
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
    ...form.value,
    code: autoCode.value ? null : form.value.code,
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
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <BaseInput
                    v-model="form.code"
                    label="Kode"
                    :help-text="autoCode ? 'Kode otomatis (nomor final bisa berbeda)' : ''"
                    :error="formErrors.code"
                    :disabled="readonly || autoCode || mode === 'edit'"
                />

                <BaseInput
                    v-model="form.name"
                    label="Nama"
                    placeholder="Masukkan nama merk"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="form.description"
                        label="Deskripsi"
                        placeholder="Masukkan deskripsi merk"
                        :error="formErrors.description"
                        :disabled="readonly"
                        :rows="3"
                    />
                </div>

                <div class="md:col-span-2">
                    <BaseCheckbox
                        v-model="form.is_active"
                        label="Status Aktif"
                        :disabled="readonly"
                    />
                </div>
            </div>
        </template>

        <template v-if="recordId" #tab-info>
            <AuditInfo
                loggable-type="product-brand"
                :loggable-id="recordId"
                :audit-data="auditData"
            />
        </template>
    </FormPage>
</template>
