<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import TablePagination from './TablePagination.vue';
import type { TableColumn, SortConfig } from '@/types';

interface Props {
    columns: TableColumn[];
    data: any[];
    loading?: boolean;
    selectable?: boolean;
    selectedRows?: any[];
    rowKey?: string;
    // Pagination
    paginated?: boolean;
    currentPage?: number;
    perPage?: number;
    total?: number;
    // Sorting
    sortable?: boolean;
    sortKey?: string;
    sortDirection?: 'asc' | 'desc';
    // Search
    searchable?: boolean;
    searchPlaceholder?: string;
    searchValue?: string;
    // Empty state
    emptyText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    selectable: false,
    selectedRows: () => [],
    rowKey: 'id',
    paginated: false,
    currentPage: 1,
    perPage: 10,
    total: 0,
    sortable: true,
    searchable: true,
    searchPlaceholder: 'Cari...',
    searchValue: '',
    emptyText: 'Tidak ada data',
});

const emit = defineEmits<{
    (e: 'update:selectedRows', value: any[]): void;
    (e: 'update:currentPage', value: number): void;
    (e: 'update:perPage', value: number): void;
    (e: 'update:searchValue', value: string): void;
    (e: 'search', value: string): void;
    (e: 'sort', value: SortConfig): void;
    (e: 'row-click', row: any): void;
}>();

const localSearch = ref(props.searchValue);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(() => props.searchValue, (val) => {
    localSearch.value = val;
});

const handleSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        emit('update:searchValue', localSearch.value);
        emit('search', localSearch.value);
    }, 300);
};

const clearSearch = () => {
    localSearch.value = '';
    emit('update:searchValue', '');
    emit('search', '');
};

const localSort = ref<SortConfig | null>(
    props.sortKey ? { key: props.sortKey, direction: props.sortDirection || 'asc' } : null
);

const isAllSelected = computed(() => {
    if (props.data.length === 0) return false;
    return props.selectedRows.length === props.data.length;
});

const isSomeSelected = computed(() => {
    return props.selectedRows.length > 0 && props.selectedRows.length < props.data.length;
});

const isRowSelected = (row: any) => {
    return props.selectedRows.some((selected) => selected[props.rowKey] === row[props.rowKey]);
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        emit('update:selectedRows', []);
    } else {
        emit('update:selectedRows', [...props.data]);
    }
};

const toggleRowSelection = (row: any) => {
    const index = props.selectedRows.findIndex(
        (selected) => selected[props.rowKey] === row[props.rowKey]
    );
    if (index === -1) {
        emit('update:selectedRows', [...props.selectedRows, row]);
    } else {
        const newSelection = [...props.selectedRows];
        newSelection.splice(index, 1);
        emit('update:selectedRows', newSelection);
    }
};

const handleSort = (column: TableColumn) => {
    if (!props.sortable || !column.sortable) return;

    let direction: 'asc' | 'desc' = 'asc';
    if (localSort.value?.key === column.key) {
        direction = localSort.value.direction === 'asc' ? 'desc' : 'asc';
    }

    localSort.value = { key: column.key, direction };
    emit('sort', localSort.value);
};

const getSortIcon = (column: TableColumn) => {
    if (!column.sortable) return null;
    if (localSort.value?.key !== column.key) return 'neutral';
    return localSort.value.direction;
};

const getCellValue = (row: any, column: TableColumn) => {
    const value = row[column.key];
    if (column.formatter) {
        return column.formatter(value, row);
    }
    return value ?? '-';
};

const getAlignClass = (align?: string) => {
    const alignments = {
        left: 'text-left',
        center: 'text-center',
        right: 'text-right',
    };
    return alignments[align as keyof typeof alignments] || 'text-left';
};
</script>

<template>
    <div class="w-full">
        <!-- Search bar -->
        <div v-if="searchable" class="mb-4 flex items-center gap-4">
            <div class="relative flex-1 max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    v-model="localSearch"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-10 text-sm placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    @input="handleSearch"
                />
                <button
                    v-if="localSearch"
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                    @click="clearSearch"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <slot name="toolbar" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <!-- Checkbox column -->
                        <th v-if="selectable" class="w-12 px-4 py-3">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                :indeterminate="isSomeSelected"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                @change="toggleSelectAll"
                            />
                        </th>
                        <!-- Data columns -->
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-600"
                            :class="[
                                getAlignClass(column.align),
                                column.sortable && sortable ? 'cursor-pointer select-none hover:bg-gray-100' : '',
                            ]"
                            :style="column.width ? { width: column.width } : {}"
                            @click="handleSort(column)"
                        >
                            <div class="flex items-center gap-1" :class="{ 'justify-center': column.align === 'center', 'justify-end': column.align === 'right' }">
                                <span>{{ column.label }}</span>
                                <template v-if="getSortIcon(column)">
                                    <svg v-if="getSortIcon(column) === 'asc'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                    <svg v-else-if="getSortIcon(column) === 'desc'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                    </svg>
                                </template>
                            </div>
                        </th>
                        <!-- Actions column -->
                        <th v-if="$slots.actions" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <!-- Loading state -->
                    <tr v-if="loading">
                        <td :colspan="columns.length + (selectable ? 1 : 0) + ($slots.actions ? 1 : 0)" class="px-4 py-12 text-center">
                            <div class="flex items-center justify-center">
                                <svg class="h-8 w-8 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span class="ml-2 text-gray-500">Memuat data...</span>
                            </div>
                        </td>
                    </tr>
                    <!-- Empty state -->
                    <tr v-else-if="data.length === 0">
                        <td :colspan="columns.length + (selectable ? 1 : 0) + ($slots.actions ? 1 : 0)" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="mt-2 text-gray-500">{{ emptyText }}</p>
                            </div>
                        </td>
                    </tr>
                    <!-- Data rows -->
                    <tr
                        v-for="(row, index) in data"
                        v-else
                        :key="row[rowKey] || index"
                        class="hover:bg-gray-50"
                        :class="{ 'bg-primary-50': isRowSelected(row) }"
                        @click="emit('row-click', row)"
                    >
                        <!-- Checkbox -->
                        <td v-if="selectable" class="px-4 py-3" @click.stop>
                            <input
                                type="checkbox"
                                :checked="isRowSelected(row)"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                @change="toggleRowSelection(row)"
                            />
                        </td>
                        <!-- Data cells -->
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="whitespace-nowrap px-4 py-3 text-sm text-gray-900"
                            :class="getAlignClass(column.align)"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="getCellValue(row, column)">
                                {{ getCellValue(row, column) }}
                            </slot>
                        </td>
                        <!-- Actions -->
                        <td v-if="$slots.actions" class="whitespace-nowrap px-4 py-3 text-right text-sm" @click.stop>
                            <slot name="actions" :row="row" :index="index" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="paginated && !loading && data.length > 0" class="mt-4">
            <TablePagination
                :current-page="currentPage"
                :per-page="perPage"
                :total="total"
                @update:current-page="emit('update:currentPage', $event)"
                @update:per-page="emit('update:perPage', $event)"
            />
        </div>
    </div>
</template>
