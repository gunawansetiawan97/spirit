<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FormPage } from '@/Components/Form';
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
    loading, saving, formErrors,
    pageTitle, setupPage, fetchData, handleSubmit, handleBack, handleEdit,
} = useFormPage({
    title: 'Role',
    apiEndpoint: '/api/roles',
    basePath: '/master/role',
}, props, emit);

const form = ref({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

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
});

const onSubmit = () => handleSubmit(() => ({ ...form.value }));
</script>

<template>
    <FormPage
        :title="pageTitle"
        :mode="mode"
        :loading="loading"
        :saving="saving"
        @submit="onSubmit"
        @back="handleBack"
        @edit="handleEdit"
    >
        <template #default="{ readonly }">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <BaseInput
                    v-model="form.code"
                    label="Kode"
                    placeholder="Masukkan kode role"
                    :error="formErrors.code"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.name"
                    label="Nama"
                    placeholder="Masukkan nama role"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="form.description"
                        label="Deskripsi"
                        placeholder="Masukkan deskripsi role"
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
    </FormPage>
</template>
