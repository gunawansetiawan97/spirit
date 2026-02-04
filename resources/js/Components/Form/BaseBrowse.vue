<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { useBrowse } from '@/Composables/useBrowse';
import { BaseModal } from '@/Components/Modal';
import TablePagination from '@/Components/Table/TablePagination.vue';
import type { BrowseConfig, BrowseColumn } from '@/types';

interface Props {
    modelValue?: string | number | null;
    config: BrowseConfig;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
    helpText?: string;
    id?: string;
    /** Pre-loaded row data to skip resolve API call (useful in edit mode) */
    rowData?: any;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    placeholder: 'Pilih...',
    rowData: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'change', value: string | number | null): void;
    (e: 'select', row: any): void;
}>();

const isModalOpen = ref(false);
const localSearch = ref('');
const selectedRow = ref<any>(null);

const browse = reactive(useBrowse(props.config));

const valueKey = computed(() => props.config.valueKey ?? 'id');

// Nested value access
const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((current, key) => current?.[key], obj);
};

// Format display using config.displayFormat
const formatDisplay = (row: any): string => {
    const fmt = props.config.displayFormat;
    if (typeof fmt === 'function') return fmt(row);
    return fmt.replace(/\{(\w+(?:\.\w+)*)\}/g, (_, key) => {
        return getNestedValue(row, key) ?? '';
    });
};

const displayLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return '';
    }
    if (!selectedRow.value) {
        return browse.resolving ? 'Memuat...' : '';
    }
    return formatDisplay(selectedRow.value);
});

// Get cell display value
const getCellDisplay = (row: any, col: BrowseColumn): string => {
    const value = getNestedValue(row, col.key);
    if (col.formatter) return col.formatter(value, row);
    if (value === null || value === undefined) return '-';
    return String(value);
};

// Row number calculation
const rowNumber = (index: number): number => {
    return (browse.currentPage - 1) * browse.perPage + index + 1;
};

// Check if row is currently selected
const isSelected = (row: any): boolean => {
    return String(row[valueKey.value]) === String(props.modelValue);
};

// Open modal
const openModal = () => {
    if (props.disabled) return;
    isModalOpen.value = true;
    localSearch.value = '';
    browse.resetState();
    browse.fetchData();
};

// Select a row
const selectRow = (row: any) => {
    const value = row[valueKey.value];
    selectedRow.value = row;
    emit('update:modelValue', value);
    emit('change', value);
    emit('select', row);
    isModalOpen.value = false;
};

// Clear selection
const clearSelection = (e: Event) => {
    e.stopPropagation();
    selectedRow.value = null;
    emit('update:modelValue', null);
    emit('change', null);
};

// Search input handler
const handleSearchInput = () => {
    browse.handleSearch(localSearch.value);
};

// Clear search
const clearSearch = () => {
    localSearch.value = '';
    browse.handleSearch('');
};

// Handle page change
const handlePageChange = (page: number) => {
    browse.handlePageChange(page);
};

// Watch rowData prop for pre-loaded data (edit mode)
watch(
    () => props.rowData,
    (row) => {
        if (row) {
            selectedRow.value = row;
        }
    },
    { immediate: true },
);

// Resolve initial value (for edit mode when rowData is not provided)
watch(
    () => props.modelValue,
    async (newVal) => {
        if (newVal === null || newVal === undefined || newVal === '') {
            selectedRow.value = null;
            return;
        }
        // Only resolve if we don't have the row data and no rowData prop
        if (!selectedRow.value && !props.rowData) {
            await browse.resolveValue(newVal);
            if (browse.resolvedRow) {
                selectedRow.value = browse.resolvedRow;
            }
        }
    },
    { immediate: true },
);

// Trigger classes (matching BaseSelect)
const triggerClasses = computed(() => [
    'relative flex w-full cursor-pointer items-center rounded-md border px-3 py-2 text-sm shadow-sm transition-colors',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    props.error
        ? 'border-danger-500 text-danger-900 focus:border-danger-500 focus:ring-danger-500'
        : 'border-gray-300 text-gray-900 focus:border-primary-500 focus:ring-primary-500',
    props.disabled ? 'cursor-not-allowed bg-gray-100' : 'bg-white',
]);

// Column alignment classes
const getAlignClass = (align?: string): string => {
    if (align === 'center') return 'text-center';
    if (align === 'right') return 'text-right';
    return 'text-left';
};
</script>

<template>
    <div class="w-full">
        <!-- Label -->
        <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-danger-500">*</span>
        </label>

        <!-- Trigger -->
        <div
            :id="id"
            tabindex="0"
            :class="triggerClasses"
            @click="openModal"
            @keydown.enter.prevent="openModal"
            @keydown.space.prevent="openModal"
        >
            <span v-if="displayLabel" class="flex-1 truncate text-gray-900">{{ displayLabel }}</span>
            <span v-else class="flex-1 truncate text-gray-400">{{ placeholder }}</span>

            <!-- Clear button -->
            <button
                v-if="displayLabel && !disabled"
                type="button"
                class="mr-1 flex-shrink-0 text-gray-400 hover:text-gray-600"
                tabindex="-1"
                @click="clearSelection"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Search icon -->
            <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <!-- Help text / Error -->
        <p v-if="helpText && !error" class="mt-1 text-sm text-gray-500">
            {{ helpText }}
        </p>
        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>

        <!-- Browse Modal -->
        <BaseModal
            v-model="isModalOpen"
            :title="config.title"
            :size="config.modalSize ?? 'xl'"
        >
            <!-- Search input -->
            <div class="mb-4">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="localSearch"
                        type="text"
                        class="block w-full rounded-md border border-gray-300 py-2 pl-10 pr-10 text-sm placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                        :placeholder="config.searchPlaceholder ?? 'Cari...'"
                        @input="handleSearchInput"
                    />
                    <button
                        v-if="localSearch"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        @click="clearSearch"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-12 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                #
                            </th>
                            <th
                                v-for="col in config.columns"
                                :key="col.key"
                                class="px-3 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500"
                                :class="getAlignClass(col.align)"
                                :style="col.width ? { width: col.width } : undefined"
                            >
                                {{ col.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <!-- Loading state -->
                        <tr v-if="browse.loading">
                            <td :colspan="config.columns.length + 1" class="px-3 py-8 text-center">
                                <svg class="mx-auto h-6 w-6 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
                            </td>
                        </tr>
                        <!-- Empty state -->
                        <tr v-else-if="browse.data.length === 0">
                            <td :colspan="config.columns.length + 1" class="px-3 py-8 text-center text-sm text-gray-500">
                                Tidak ada data
                            </td>
                        </tr>
                        <!-- Data rows -->
                        <tr
                            v-for="(row, index) in browse.data"
                            v-else
                            :key="row[valueKey]"
                            class="cursor-pointer transition-colors hover:bg-primary-50"
                            :class="{ 'bg-primary-50 font-medium': isSelected(row) }"
                            @click="selectRow(row)"
                        >
                            <td class="whitespace-nowrap px-3 py-2 text-center text-sm text-gray-500">
                                {{ rowNumber(index) }}
                            </td>
                            <td
                                v-for="col in config.columns"
                                :key="col.key"
                                class="px-3 py-2 text-sm text-gray-900"
                                :class="getAlignClass(col.align)"
                            >
                                {{ getCellDisplay(row, col) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="browse.total > browse.perPage" class="mt-4">
                <TablePagination
                    :current-page="browse.currentPage"
                    :per-page="browse.perPage"
                    :total="browse.total"
                    @update:current-page="handlePageChange"
                />
            </div>
        </BaseModal>
    </div>
</template>
