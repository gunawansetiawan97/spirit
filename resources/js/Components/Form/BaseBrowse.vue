<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
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
    rowData?: any;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    placeholder: 'Cari...',
    rowData: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'change', value: string | number | null): void;
    (e: 'select', row: any): void;
    (e: 'navigate', route: string): void;
}>();

// State
const isModalOpen = ref(false);
const isDropdownOpen = ref(false);
const inputSearch = ref('');
const modalSearch = ref('');
const selectedRow = ref<any>(null);
const highlightedIndex = ref(-1);
const containerRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const dropdownRef = ref<HTMLElement | null>(null);

// Separate browse instances for dropdown (autocomplete) and modal
const dropdown = reactive(useBrowse(props.config));
const modal = reactive(useBrowse(props.config));

const valueKey = computed(() => props.config.valueKey ?? 'id');

// --- Utility functions ---

const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((current, key) => current?.[key], obj);
};

const formatDisplay = (row: any, fmt?: string | ((row: any) => string)): string => {
    const format = fmt ?? props.config.displayFormat;
    if (typeof format === 'function') return format(row);
    return format.replace(/\{(\w+(?:\.\w+)*)\}/g, (_, key) => {
        return getNestedValue(row, key) ?? '';
    });
};

const formatDropdownItem = (row: any): string => {
    return formatDisplay(row, props.config.dropdownFormat ?? props.config.displayFormat);
};

// --- Computed ---

const displayLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return '';
    }
    if (!selectedRow.value) {
        return dropdown.resolving ? 'Memuat...' : '';
    }
    return formatDisplay(selectedRow.value);
});

const isInputMode = computed(() => !displayLabel.value || isDropdownOpen.value);

// --- Dropdown (autocomplete) ---

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const openDropdown = () => {
    if (props.disabled) return;
    isDropdownOpen.value = true;
    highlightedIndex.value = -1;
    // Fetch if we don't have data yet
    if (dropdown.data.length === 0) {
        dropdown.resetState();
        dropdown.fetchData();
    }
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
    highlightedIndex.value = -1;
    // If we have a selected row, restore the display
    if (selectedRow.value) {
        inputSearch.value = '';
    }
};

const handleInputFocus = () => {
    inputSearch.value = '';
    openDropdown();
};

const handleInputChange = () => {
    if (!isDropdownOpen.value) openDropdown();
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        dropdown.search = inputSearch.value;
        dropdown.currentPage = 1;
        dropdown.fetchData();
    }, 300);
    highlightedIndex.value = -1;
};

const handleInputKeydown = (e: KeyboardEvent) => {
    if (!isDropdownOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            e.preventDefault();
            openDropdown();
        }
        return;
    }

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (highlightedIndex.value < dropdown.data.length - 1) {
                highlightedIndex.value++;
                scrollToHighlighted();
            }
            break;
        case 'ArrowUp':
            e.preventDefault();
            if (highlightedIndex.value > 0) {
                highlightedIndex.value--;
                scrollToHighlighted();
            }
            break;
        case 'Enter':
            e.preventDefault();
            if (highlightedIndex.value >= 0 && highlightedIndex.value < dropdown.data.length) {
                selectRow(dropdown.data[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            e.preventDefault();
            closeDropdown();
            break;
    }
};

const scrollToHighlighted = () => {
    const el = dropdownRef.value?.querySelector(`[data-index="${highlightedIndex.value}"]`);
    el?.scrollIntoView({ block: 'nearest' });
};

// --- Selection ---

const selectRow = (row: any) => {
    const value = row[valueKey.value];
    selectedRow.value = row;
    inputSearch.value = '';
    isDropdownOpen.value = false;
    isModalOpen.value = false;
    emit('update:modelValue', value);
    emit('change', value);
    emit('select', row);
};

const clearSelection = () => {
    selectedRow.value = null;
    inputSearch.value = '';
    emit('update:modelValue', null);
    emit('change', null);
};

// --- Action buttons ---

const handleCreate = () => {
    if (props.config.createRoute) {
        emit('navigate', props.config.createRoute);
    }
};

// --- Dropdown pagination ---

const dropdownHasPages = computed(() => dropdown.lastPage > 1);

const dropdownPrevPage = () => {
    if (dropdown.currentPage > 1) {
        dropdown.currentPage--;
        dropdown.fetchData();
    }
};

const dropdownNextPage = () => {
    if (dropdown.currentPage < dropdown.lastPage) {
        dropdown.currentPage++;
        dropdown.fetchData();
    }
};

// --- Modal ---

const openModal = () => {
    if (props.disabled) return;
    closeDropdown();
    isModalOpen.value = true;
    modalSearch.value = '';
    modal.resetState();
    modal.fetchData();
};

const handleModalSearch = () => {
    modal.handleSearch(modalSearch.value);
};

const clearModalSearch = () => {
    modalSearch.value = '';
    modal.handleSearch('');
};

const handlePageChange = (page: number) => {
    modal.handlePageChange(page);
};

// --- Table helpers ---

const getCellDisplay = (row: any, col: BrowseColumn): string => {
    const value = getNestedValue(row, col.key);
    if (col.formatter) return col.formatter(value, row);
    if (value === null || value === undefined) return '-';
    return String(value);
};

const rowNumber = (index: number): number => {
    return (modal.currentPage - 1) * modal.perPage + index + 1;
};

const isSelected = (row: any): boolean => {
    return String(row[valueKey.value]) === String(props.modelValue);
};

const getAlignClass = (align?: string): string => {
    if (align === 'center') return 'text-center';
    if (align === 'right') return 'text-right';
    return 'text-left';
};

// --- Click outside ---

const handleClickOutside = (e: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    if (searchTimeout) clearTimeout(searchTimeout);
});

// --- Watch for pre-loaded data ---

watch(
    () => props.rowData,
    (row) => {
        if (row) selectedRow.value = row;
    },
    { immediate: true },
);

watch(
    () => props.modelValue,
    async (newVal) => {
        if (newVal === null || newVal === undefined || newVal === '') {
            selectedRow.value = null;
            return;
        }
        if (!selectedRow.value && !props.rowData) {
            await dropdown.resolveValue(newVal);
            if (dropdown.resolvedRow) {
                selectedRow.value = dropdown.resolvedRow;
            }
        }
    },
    { immediate: true },
);

// --- Input classes ---

const inputClasses = computed(() => [
    'block w-full rounded-l-md border py-2 pl-3 pr-2 text-sm shadow-sm transition-colors',
    'focus:outline-none focus:ring-1 focus:ring-offset-0',
    props.error
        ? 'border-danger-500 text-danger-900 focus:border-danger-500 focus:ring-danger-500'
        : 'border-gray-300 text-gray-900 focus:border-primary-500 focus:ring-primary-500',
    props.disabled ? 'cursor-not-allowed bg-gray-100' : 'bg-white',
]);
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Label -->
        <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-danger-500">*</span>
        </label>

        <!-- Input + Action Buttons -->
        <div class="flex">
            <!-- Input field -->
            <div class="relative flex-1">
                <input
                    v-if="isInputMode"
                    :id="id"
                    ref="inputRef"
                    v-model="inputSearch"
                    type="text"
                    :class="inputClasses"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    autocomplete="off"
                    @focus="handleInputFocus"
                    @input="handleInputChange"
                    @keydown="handleInputKeydown"
                />
                <div
                    v-else
                    :class="inputClasses"
                    class="flex cursor-pointer items-center"
                    @click="() => { clearSelection(); inputRef?.focus(); }"
                >
                    <span class="flex-1 truncate">{{ displayLabel }}</span>
                    <button
                        v-if="!disabled"
                        type="button"
                        class="ml-1 flex-shrink-0 text-gray-400 hover:text-gray-600"
                        tabindex="-1"
                        @click.stop="clearSelection"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex">
                <!-- Create (+) button -->
                <button
                    v-if="config.createRoute"
                    type="button"
                    class="inline-flex items-center border border-l-0 border-gray-300 bg-gray-50 px-2 text-gray-500 hover:bg-gray-100 hover:text-primary-600 focus:outline-none"
                    :disabled="disabled"
                    title="Tambah baru"
                    @click="handleCreate"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>

                <!-- Browse (magnifying glass) button -->
                <button
                    type="button"
                    class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-2 text-gray-500 hover:bg-gray-100 hover:text-primary-600 focus:outline-none"
                    :disabled="disabled"
                    title="Browse"
                    @click="openModal"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Autocomplete Dropdown -->
        <div
            v-if="isDropdownOpen"
            ref="dropdownRef"
            class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg"
        >
            <!-- Loading -->
            <div v-if="dropdown.loading" class="px-3 py-4 text-center text-sm text-gray-500">
                <svg class="mx-auto h-5 w-5 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>
            <!-- Empty -->
            <div v-else-if="dropdown.data.length === 0" class="px-3 py-3 text-sm text-gray-500">
                Tidak ada hasil
            </div>
            <!-- Results -->
            <template v-else>
                <ul class="max-h-60 overflow-auto py-1">
                    <li
                        v-for="(row, index) in dropdown.data"
                        :key="row[valueKey]"
                        :data-index="index"
                        class="cursor-pointer select-none px-3 py-2 text-sm transition-colors"
                        :class="[
                            highlightedIndex === index
                                ? 'bg-primary-50 text-primary-700'
                                : 'text-gray-900 hover:bg-gray-50',
                            isSelected(row) ? 'font-medium' : '',
                        ]"
                        @click="selectRow(row)"
                        @mouseenter="highlightedIndex = index"
                    >
                        {{ formatDropdownItem(row) }}
                    </li>
                </ul>
                <!-- Dropdown pagination -->
                <div v-if="dropdownHasPages" class="flex items-center justify-between border-t border-gray-200 px-3 py-1.5">
                    <span class="text-xs text-gray-500">Hal {{ dropdown.currentPage }} / {{ dropdown.lastPage }}</span>
                    <div class="flex gap-1">
                        <button
                            type="button"
                            class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 disabled:opacity-30 disabled:hover:bg-transparent"
                            :disabled="dropdown.currentPage <= 1"
                            @click.stop="dropdownPrevPage"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 disabled:opacity-30 disabled:hover:bg-transparent"
                            :disabled="dropdown.currentPage >= dropdown.lastPage"
                            @click.stop="dropdownNextPage"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
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
            :size="config.modalSize ?? 'full'"
        >
            <!-- Search input -->
            <div class="mb-4">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="modalSearch"
                        type="text"
                        class="block w-full rounded-md border border-gray-300 py-2 pl-10 pr-10 text-sm placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                        :placeholder="config.searchPlaceholder ?? 'Cari...'"
                        @input="handleModalSearch"
                    />
                    <button
                        v-if="modalSearch"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        @click="clearModalSearch"
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
                        <tr v-if="modal.loading">
                            <td :colspan="config.columns.length + 1" class="px-3 py-8 text-center">
                                <svg class="mx-auto h-6 w-6 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
                            </td>
                        </tr>
                        <tr v-else-if="modal.data.length === 0">
                            <td :colspan="config.columns.length + 1" class="px-3 py-8 text-center text-sm text-gray-500">
                                Tidak ada data
                            </td>
                        </tr>
                        <tr
                            v-for="(row, index) in modal.data"
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
            <div v-if="modal.total > modal.perPage" class="mt-4">
                <TablePagination
                    :current-page="modal.currentPage"
                    :per-page="modal.perPage"
                    :total="modal.total"
                    @update:current-page="handlePageChange"
                />
            </div>
        </BaseModal>
    </div>
</template>
