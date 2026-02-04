<script setup lang="ts">
import { ref, reactive } from 'vue';
import {
    BaseInput,
    BaseSelect,
    BaseTextarea,
    BaseCheckbox,
    BaseRadio,
    BaseDatePicker,
    BaseButton,
    FormGroup,
} from '@/Components/Form';
import { BaseModal, ConfirmDialog, FormModal } from '@/Components/Modal';
import { DataTable, TableFilter } from '@/Components/Table';
import type { TableColumn, SelectOption } from '@/types';

// Form demo data
const formData = reactive({
    name: '',
    email: '',
    category: null as string | null,
    description: '',
    isActive: false,
    gender: null as string | null,
    birthDate: '',
});

const categoryOptions: SelectOption[] = [
    { value: 'electronics', label: 'Elektronik' },
    { value: 'clothing', label: 'Pakaian' },
    { value: 'food', label: 'Makanan' },
];

const genderOptions: SelectOption[] = [
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
];

// Modal demo
const showModal = ref(false);
const showConfirmDialog = ref(false);
const showFormModal = ref(false);
const isLoading = ref(false);

const handleConfirm = () => {
    isLoading.value = true;
    setTimeout(() => {
        isLoading.value = false;
        showConfirmDialog.value = false;
        alert('Confirmed!');
    }, 1500);
};

const handleFormSubmit = () => {
    isLoading.value = true;
    setTimeout(() => {
        isLoading.value = false;
        showFormModal.value = false;
        alert('Form submitted!');
    }, 1500);
};

// Table demo
const tableColumns: TableColumn[] = [
    { key: 'id', label: 'ID', width: '80px', sortable: true },
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'created_at', label: 'Tanggal', sortable: true },
];

const tableData = [
    { id: 1, name: 'John Doe', email: 'john@example.com', status: 'Active', created_at: '2024-01-15' },
    { id: 2, name: 'Jane Smith', email: 'jane@example.com', status: 'Inactive', created_at: '2024-01-16' },
    { id: 3, name: 'Bob Johnson', email: 'bob@example.com', status: 'Active', created_at: '2024-01-17' },
    { id: 4, name: 'Alice Brown', email: 'alice@example.com', status: 'Active', created_at: '2024-01-18' },
    { id: 5, name: 'Charlie Wilson', email: 'charlie@example.com', status: 'Inactive', created_at: '2024-01-19' },
];

const selectedRows = ref<any[]>([]);
const currentPage = ref(1);
const perPage = ref(10);

const handleSort = (sort: any) => {
    console.log('Sort:', sort);
};

const handleRowClick = (row: any) => {
    console.log('Row clicked:', row);
};
</script>

<template>
    <div class="space-y-8 p-6">
        <h1 class="text-2xl font-bold text-gray-900">Component Demo</h1>

        <!-- Form Components Section -->
        <section class="rounded-lg bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Form Components</h2>

            <div class="grid gap-4 md:grid-cols-2">
                <BaseInput
                    v-model="formData.name"
                    label="Nama"
                    placeholder="Masukkan nama"
                    required
                />

                <BaseInput
                    v-model="formData.email"
                    type="email"
                    label="Email"
                    placeholder="Masukkan email"
                    help-text="Gunakan email yang valid"
                />

                <BaseSelect
                    v-model="formData.category"
                    :options="categoryOptions"
                    label="Kategori"
                    placeholder="Pilih kategori"
                />

                <BaseDatePicker
                    v-model="formData.birthDate"
                    label="Tanggal Lahir"
                />

                <div class="md:col-span-2">
                    <BaseTextarea
                        v-model="formData.description"
                        label="Deskripsi"
                        placeholder="Masukkan deskripsi"
                        :rows="3"
                        :maxlength="200"
                    />
                </div>

                <BaseCheckbox
                    v-model="formData.isActive"
                    label="Aktif"
                />

                <BaseRadio
                    v-model="formData.gender"
                    :options="genderOptions"
                    name="gender"
                    label="Jenis Kelamin"
                    inline
                />
            </div>

            <div class="mt-4 flex gap-2">
                <BaseButton variant="primary">Simpan</BaseButton>
                <BaseButton variant="secondary">Batal</BaseButton>
                <BaseButton variant="danger">Hapus</BaseButton>
                <BaseButton variant="primary" loading>Loading...</BaseButton>
            </div>
        </section>

        <!-- Button Variants Section -->
        <section class="rounded-lg bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Button Variants</h2>

            <div class="flex flex-wrap gap-2">
                <BaseButton variant="primary">Primary</BaseButton>
                <BaseButton variant="secondary">Secondary</BaseButton>
                <BaseButton variant="success">Success</BaseButton>
                <BaseButton variant="warning">Warning</BaseButton>
                <BaseButton variant="danger">Danger</BaseButton>
                <BaseButton variant="ghost">Ghost</BaseButton>
            </div>

            <h3 class="mb-2 mt-4 text-sm font-medium text-gray-700">Sizes</h3>
            <div class="flex flex-wrap items-center gap-2">
                <BaseButton size="sm">Small</BaseButton>
                <BaseButton size="md">Medium</BaseButton>
                <BaseButton size="lg">Large</BaseButton>
            </div>
        </section>

        <!-- Modal Components Section -->
        <section class="rounded-lg bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Modal Components</h2>

            <div class="flex flex-wrap gap-2">
                <BaseButton @click="showModal = true">Open Modal</BaseButton>
                <BaseButton variant="warning" @click="showConfirmDialog = true">Confirm Dialog</BaseButton>
                <BaseButton variant="success" @click="showFormModal = true">Form Modal</BaseButton>
            </div>

            <!-- Base Modal -->
            <BaseModal v-model="showModal" title="Modal Title" size="md">
                <p class="text-gray-600">
                    This is a basic modal content. You can put anything here.
                </p>
                <template #footer>
                    <div class="flex justify-end gap-2">
                        <BaseButton variant="secondary" @click="showModal = false">Close</BaseButton>
                        <BaseButton variant="primary">Save</BaseButton>
                    </div>
                </template>
            </BaseModal>

            <!-- Confirm Dialog -->
            <ConfirmDialog
                v-model="showConfirmDialog"
                title="Hapus Data"
                message="Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan."
                variant="danger"
                confirm-text="Hapus"
                :loading="isLoading"
                @confirm="handleConfirm"
            />

            <!-- Form Modal -->
            <FormModal
                v-model="showFormModal"
                title="Tambah Data Baru"
                :loading="isLoading"
                @submit="handleFormSubmit"
            >
                <div class="space-y-4">
                    <BaseInput label="Nama" placeholder="Masukkan nama" required />
                    <BaseInput type="email" label="Email" placeholder="Masukkan email" />
                </div>
            </FormModal>
        </section>

        <!-- Table Component Section -->
        <section class="rounded-lg bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Table Component</h2>

            <DataTable
                :columns="tableColumns"
                :data="tableData"
                :selectable="true"
                :selected-rows="selectedRows"
                :paginated="true"
                :current-page="currentPage"
                :per-page="perPage"
                :total="tableData.length"
                @update:selected-rows="selectedRows = $event"
                @update:current-page="currentPage = $event"
                @update:per-page="perPage = $event"
                @sort="handleSort"
                @row-click="handleRowClick"
            >
                <template #cell-status="{ value }">
                    <span
                        class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                        :class="value === 'Active' ? 'bg-success-100 text-success-800' : 'bg-gray-100 text-gray-800'"
                    >
                        {{ value }}
                    </span>
                </template>
                <template #actions="{ row }">
                    <div class="flex justify-end gap-1">
                        <BaseButton size="sm" variant="ghost">Edit</BaseButton>
                        <BaseButton size="sm" variant="ghost" class="text-danger-600">Hapus</BaseButton>
                    </div>
                </template>
            </DataTable>

            <p v-if="selectedRows.length > 0" class="mt-2 text-sm text-gray-600">
                {{ selectedRows.length }} row(s) selected
            </p>
        </section>
    </div>
</template>
