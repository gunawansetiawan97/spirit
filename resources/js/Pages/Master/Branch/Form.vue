<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useUiStore } from '@/stores/ui';
import { FormPage } from '@/Components/Form';
import { BaseInput, BaseTextarea, BaseCheckbox } from '@/Components/Form';
import axios from 'axios';

type FormMode = 'create' | 'edit' | 'view';

interface Props {
    id?: string | number;
    mode: FormMode;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'create',
});

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const uiStore = useUiStore();

const loading = ref(false);
const saving = ref(false);

const form = ref({
    code: '',
    name: '',
    address: '',
    phone: '',
    is_active: true,
});

const formErrors = ref<Record<string, string>>({});

const pageTitle = computed(() => {
    return 'Cabang';
});

const fetchData = async () => {
    if (!props.id) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/branches/${props.id}`);
        const data = response.data.data;
        form.value = {
            code: data.code,
            name: data.name,
            address: data.address || '',
            phone: data.phone || '',
            is_active: data.is_active,
        };
    } catch (error: any) {
        console.error('Failed to fetch branch:', error);
        alert('Gagal memuat data cabang');
        emit('navigate', '/master/branch');
    } finally {
        loading.value = false;
    }
};

const handleSubmit = async () => {
    formErrors.value = {};
    saving.value = true;

    try {
        if (props.mode === 'edit' && props.id) {
            await axios.put(`/api/branches/${props.id}`, form.value);
        } else {
            await axios.post('/api/branches', form.value);
        }
        emit('navigate', '/master/branch');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            formErrors.value = Object.fromEntries(
                Object.entries(error.response.data.errors).map(([key, value]) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ])
            ) as Record<string, string>;
        } else {
            alert(error.response?.data?.message || 'Gagal menyimpan data');
        }
    } finally {
        saving.value = false;
    }
};

const handleBack = () => {
    emit('navigate', '/master/branch');
};

const handleEdit = () => {
    if (props.id) {
        emit('navigate', `/master/branch/${props.id}/edit`);
    }
};

onMounted(() => {
    const titles: Record<FormMode, string> = {
        create: 'Tambah Cabang',
        edit: 'Edit Cabang',
        view: 'Detail Cabang',
    };
    uiStore.setPageTitle(titles[props.mode]);
    uiStore.setPageActions([]);

    if (props.id && (props.mode === 'edit' || props.mode === 'view')) {
        fetchData();
    }
});
</script>

<template>
    <FormPage
        :title="pageTitle"
        :mode="mode"
        :loading="loading"
        :saving="saving"
        @submit="handleSubmit"
        @back="handleBack"
        @edit="handleEdit"
    >
        <template #default="{ readonly }">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <BaseInput
                    v-model="form.code"
                    label="Kode"
                    placeholder="Masukkan kode cabang"
                    :error="formErrors.code"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.name"
                    label="Nama"
                    placeholder="Masukkan nama cabang"
                    :error="formErrors.name"
                    :disabled="readonly"
                    required
                />

                <BaseInput
                    v-model="form.phone"
                    label="Telepon"
                    placeholder="Masukkan nomor telepon"
                    :error="formErrors.phone"
                    :disabled="readonly"
                />

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="form.address"
                        label="Alamat"
                        placeholder="Masukkan alamat cabang"
                        :error="formErrors.address"
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
