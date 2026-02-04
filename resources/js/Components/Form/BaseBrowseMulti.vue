<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useBrowse } from '@/Composables/useBrowse';
import { BaseModal } from '@/Components/Modal';
import TablePagination from '@/Components/Table/TablePagination.vue';
import type { BrowseConfig, BrowseColumn } from '@/types';

interface Props {
    modelValue?: (string | number)[];
    config: BrowseConfig;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
    helpText?: string;
    id?: string;
    rowsData?: any[];
    max?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    placeholder: 'Cari...',
    rowsData: undefined,
    max: undefined,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: (string | number)[]): void;
    (e: 'change', value: (string | number)[]): void;
    (e: 'select', row: any): void;
    (e: 'unselect', row: any): void;
    (e: 'navigate', route: string): void;
}>();

// State
const isModalOpen = ref(false);
const isDropdownOpen = ref(false);
const inputSearch = ref('');
const modalSearch = ref('');
const selectedRows = ref<any[]>([]);
const highlightedIndex = ref(-1);
const containerRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const dropdownRef = ref<HTMLElement | null>(null);

// Separate browse instances for dropdown (autocomplete) and modal
const dropdown = reactive(useBrowse(props.config));
const modal = reactive(useBrowse(props.config));

const valueKey = computed(() => props.config.valueKey ?? 'id');

const canAddMore = computed(() => {
    if (!props.max) return true;
    return selectedRows.value.length < props.max;
});

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

// --- Selection helpers ---

const selectedIdSet = computed(() => {
    return new Set(props.modelValue?.map(String) ?? []);
});

const isRowSelected = (row: any): boolean => {
    return selectedIdSet.value.has(String(row[valueKey.value]));
};

const isAllPageSelected = computed(() => {
    if (modal.data.length === 0) return false;
    return modal.data.every((row: any) => isRowSelected(row));
});

const isSomePageSelected = computed(() => {
    if (modal.data.length === 0) return false;
    return modal.data.some((row: any) => isRowSelected(row)) && !isAllPageSelected.value;
});

// --- Dropdown (autocomplete) ---

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const openDropdown = () => {
    if (props.disabled) return;
    isDropdownOpen.value = true;
    highlightedIndex.value = -1;
    if (dropdown.data.length === 0) {
        dropdown.resetState();
        dropdown.fetchData();
    }
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
    highlightedIndex.value = -1;
    inputSearch.value = '';
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
    // Backspace on empty input removes last chip
    if (e.key === 'Backspace' && !inputSearch.value && selectedRows.value.length > 0) {
        removeRow(selectedRows.value.length - 1);
        return;
    }

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
                toggleRow(dropdown.data[highlightedIndex.value]);
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

const toggleRow = (row: any) => {
    const id = row[valueKey.value];
    if (isRowSelected(row)) {
        // Remove
        const newRows = selectedRows.value.filter(r => String(r[valueKey.value]) !== String(id));
        selectedRows.value = newRows;
        const newValues = newRows.map(r => r[valueKey.value]);
        emit('update:modelValue', newValues);
        emit('change', newValues);
        emit('unselect', row);
    } else {
        // Add (check max)
        if (!canAddMore.value) return;
        const newRows = [...selectedRows.value, row];
        selectedRows.value = newRows;
        const newValues = newRows.map(r => r[valueKey.value]);
        emit('update:modelValue', newValues);
        emit('change', newValues);
        emit('select', row);
    }
    inputSearch.value = '';
};

const removeRow = (index: number) => {
    const row = selectedRows.value[index];
    const newRows = [...selectedRows.value];
    newRows.splice(index, 1);
    selectedRows.value = newRows;
    const newValues = newRows.map(r => r[valueKey.value]);
    emit('update:modelValue', newValues);
    emit('change', newValues);
    if (row) emit('unselect', row);
    nextTick(() => inputRef.value?.focus());
};

const clearAll = () => {
    const oldRows = [...selectedRows.value];
    selectedRows.value = [];
    emit('update:modelValue', []);
    emit('change', []);
    oldRows.forEach(row => emit('unselect', row));
    nextTick(() => inputRef.value?.focus());
};

const toggleAllPage = () => {
    if (isAllPageSelected.value) {
        // Unselect all on current page
        const pageIds = new Set(modal.data.map((r: any) => String(r[valueKey.value])));
        const newRows = selectedRows.value.filter(r => !pageIds.has(String(r[valueKey.value])));
        selectedRows.value = newRows;
        const newValues = newRows.map(r => r[valueKey.value]);
        emit('update:modelValue', newValues);
        emit('change', newValues);
    } else {
        // Select all on current page (that aren't already selected)
        const existing = new Set(selectedRows.value.map(r => String(r[valueKey.value])));
        const toAdd = modal.data.filter((r: any) => !existing.has(String(r[valueKey.value])));
        if (props.max) {
            const remaining = props.max - selectedRows.value.length;
            toAdd.splice(remaining);
        }
        const newRows = [...selectedRows.value, ...toAdd];
        selectedRows.value = newRows;
        const newValues = newRows.map(r => r[valueKey.value]);
        emit('update:modelValue', newValues);
        emit('change', newValues);
    }
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

const closeModal = () => {
    isModalOpen.value = false;
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
    () => props.rowsData,
    (rows) => {
        if (rows && rows.length > 0) {
            selectedRows.value = [...rows];
        }
    },
    { immediate: true },
);

watch(
    () => props.modelValue,
    async (newVal) => {
        if (!newVal || newVal.length === 0) {
            selectedRows.value = [];
            return;
        }
        // Only resolve if we don't already have all rows loaded
        const loadedIds = new Set(selectedRows.value.map(r => String(r[valueKey.value])));
        const missingIds = newVal.filter(id => !loadedIds.has(String(id)));
        if (missingIds.length > 0 && !props.rowsData) {
            for (const id of missingIds) {
                await dropdown.resolveValue(id);
                if (dropdown.resolvedRow) {
                    selectedRows.value = [...selectedRows.value, dropdown.resolvedRow];
                }
            }
        }
    },
    { immediate: true },
);

// --- Input classes ---

const containerClasses = computed(() => [
    'flex flex-wrap items-center gap-1 rounded-l-md border px-2 py-1.5 text-sm shadow-sm transition-colors min-h-[38px]',
    'focus-within:outline-none focus-within:ring-1 focus-within:ring-offset-0',
    props.error
        ? 'border-danger-500 focus-within:border-danger-500 focus-within:ring-danger-500'
        : 'border-gray-300 focus-within:border-primary-500 focus-within:ring-primary-500',
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

        <!-- Chips + Input + Action Buttons -->
        <div class="flex">
            <!-- Chips container + input -->
            <div
                :class="containerClasses"
                class="flex-1 cursor-text"
                @click="inputRef?.focus()"
            >
                <!-- Selected chips -->
                <span
                    v-for="(row, index) in selectedRows"
                    :key="row[valueKey]"
                    class="inline-flex items-center gap-1 rounded bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700"
                >
                    <span class="max-w-[150px] truncate">{{ formatDisplay(row) }}</span>
                    <button
                        v-if="!disabled"
                        type="button"
                        class="flex-shrink-0 text-primary-400 hover:text-primary-600"
                        tabindex="-1"
                        @click.stop="removeRow(index)"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>

                <!-- Search input -->
                <input
                    :id="id"
                    ref="inputRef"
                    v-model="inputSearch"
                    type="text"
                    class="min-w-[80px] flex-1 border-none bg-transparent p-0 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
                    :placeholder="selectedRows.length === 0 ? placeholder : ''"
                    :disabled="disabled"
                    autocomplete="off"
                    @focus="handleInputFocus"
                    @input="handleInputChange"
                    @keydown="handleInputKeydown"
                />

                <!-- Clear all button -->
                <button
                    v-if="selectedRows.length > 0 && !disabled"
                    type="button"
                    class="ml-auto flex-shrink-0 text-gray-400 hover:text-gray-600"
                    tabindex="-1"
                    @click.stop="clearAll"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
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
                        class="flex cursor-pointer select-none items-center gap-2 px-3 py-2 text-sm transition-colors"
                        :class="[
                            highlightedIndex === index
                                ? 'bg-primary-50 text-primary-700'
                                : 'text-gray-900 hover:bg-gray-50',
                        ]"
                        @click="toggleRow(row)"
                        @mouseenter="highlightedIndex = index"
                    >
                        <!-- Checkmark -->
                        <svg
                            v-if="isRowSelected(row)"
                            class="h-4 w-4 flex-shrink-0 text-primary-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span v-else class="h-4 w-4 flex-shrink-0" />
                        <span :class="{ 'font-medium': isRowSelected(row) }">
                            {{ formatDropdownItem(row) }}
                        </span>
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
                            <!-- Checkbox header -->
                            <th class="w-10 px-3 py-3 text-center">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    :checked="isAllPageSelected"
                                    :indeterminate="isSomePageSelected"
                                    :disabled="modal.loading || modal.data.length === 0"
                                    @change="toggleAllPage"
                                />
                            </th>
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
                            <td :colspan="config.columns.length + 2" class="px-3 py-8 text-center">
                                <svg class="mx-auto h-6 w-6 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
                            </td>
                        </tr>
                        <tr v-else-if="modal.data.length === 0">
                            <td :colspan="config.columns.length + 2" class="px-3 py-8 text-center text-sm text-gray-500">
                                Tidak ada data
                            </td>
                        </tr>
                        <tr
                            v-for="(row, index) in modal.data"
                            v-else
                            :key="row[valueKey]"
                            class="cursor-pointer transition-colors hover:bg-primary-50"
                            :class="{ 'bg-primary-50': isRowSelected(row) }"
                            @click="toggleRow(row)"
                        >
                            <!-- Checkbox -->
                            <td class="px-3 py-2 text-center">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    :checked="isRowSelected(row)"
                                    @click.stop
                                    @change="toggleRow(row)"
                                />
                            </td>
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

            <!-- Footer -->
            <template #footer>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        {{ selectedRows.length }} item dipilih
                        <template v-if="max"> (maks {{ max }})</template>
                    </span>
                    <button
                        type="button"
                        class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        @click="closeModal"
                    >
                        Selesai
                    </button>
                </div>
            </template>
        </BaseModal>
    </div>
</template>
