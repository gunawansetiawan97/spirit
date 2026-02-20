<script setup lang="ts">
import { computed } from 'vue';

export interface GridColumn {
    key: string;
    label: string;
    width?: string;
}

interface Props {
    modelValue: any[];
    columns: GridColumn[];
    disabled?: boolean;
    title?: string;
    addable?: boolean;
    deletable?: boolean;
    copyable?: boolean;
    newRow?: () => any;
    error?: string;
    /** Unique constraints. Each entry is a column key (string) or composite keys (string[]).
     *  e.g. ['branch_id'] — branch_id must be unique per row
     *  e.g. [['branch_id', 'tipe']] — combination must be unique
     *  e.g. ['branch_id', ['product_id', 'batch']] — both rules applied
     */
    uniqueKeys?: (string | string[])[];
    /** Column keys that must be filled (non-null, non-empty) */
    requiredKeys?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    addable: true,
    deletable: true,
    copyable: true,
    newRow: () => () => ({}),
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: any[]): void;
}>();

const rows = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

// Duplicate detection: returns Set of row indices that violate a unique constraint
const duplicateIndices = computed<Set<number>>(() => {
    if (!props.uniqueKeys || props.uniqueKeys.length === 0) return new Set();
    const dupes = new Set<number>();
    for (const rule of props.uniqueKeys) {
        const keys = Array.isArray(rule) ? rule : [rule];
        const seen = new Map<string, number>();
        rows.value.forEach((row, idx) => {
            const vals = keys.map(k => row[k]);
            // Skip rows where any key is null/undefined/empty
            if (vals.some(v => v === null || v === undefined || v === '')) return;
            const hash = vals.map(v => String(v)).join('\x00');
            if (seen.has(hash)) {
                dupes.add(seen.get(hash)!);
                dupes.add(idx);
            } else {
                seen.set(hash, idx);
            }
        });
    }
    return dupes;
});

// Required validation: returns Map of rowIndex -> Set of empty required column keys
const emptyRequiredCells = computed<Map<number, Set<string>>>(() => {
    const result = new Map<number, Set<string>>();
    if (!props.requiredKeys || props.requiredKeys.length === 0) return result;
    rows.value.forEach((row, idx) => {
        for (const key of props.requiredKeys!) {
            const val = row[key];
            if (val === null || val === undefined || val === '') {
                if (!result.has(idx)) result.set(idx, new Set());
                result.get(idx)!.add(key);
            }
        }
    });
    return result;
});

const hasDuplicates = computed(() => duplicateIndices.value.size > 0);
const hasEmptyRequired = computed(() => emptyRequiredCells.value.size > 0);
const hasErrors = computed(() => hasDuplicates.value || hasEmptyRequired.value);

defineExpose({ hasDuplicates, hasEmptyRequired, hasErrors });

const canAddRow = computed(() => !hasErrors.value);

const addRow = () => {
    if (!canAddRow.value) return;
    rows.value = [...rows.value, props.newRow()];
};

const copyRow = () => {
    if (!canAddRow.value || rows.value.length === 0) return;
    const lastRow = rows.value[rows.value.length - 1];
    rows.value = [...rows.value, { ...lastRow }];
};

const deleteRow = (index: number) => {
    rows.value = rows.value.filter((_, i) => i !== index);
};

const isRowError = (index: number) => {
    return duplicateIndices.value.has(index) || emptyRequiredCells.value.has(index);
};

const isCellEmpty = (index: number, key: string) => {
    return emptyRequiredCells.value.get(index)?.has(key) ?? false;
};
</script>

<template>
    <div class="w-full">
        <label v-if="title" class="mb-1 block text-sm font-medium text-gray-700">
            {{ title }}
        </label>

        <div class="rounded-md border" :class="error || (hasErrors && !disabled) ? 'border-danger-500' : 'border-gray-300'">
            <!-- Table -->
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="w-10 rounded-tl-md px-2 py-1.5 text-center font-medium text-gray-600">No.</th>
                        <th
                            v-for="(col, ci) in columns"
                            :key="col.key"
                            class="px-2 py-1.5 text-left font-medium text-gray-600"
                            :class="{ 'rounded-tr-md': (deletable && !disabled) ? false : ci === columns.length - 1 }"
                            :style="col.width ? { width: col.width } : {}"
                        >
                            {{ col.label }}
                        </th>
                        <th v-if="deletable && !disabled" class="w-10 rounded-tr-md px-2 py-1.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in rows"
                        :key="index"
                        class="border-b border-gray-100 last:border-b-0"
                        :class="{ 'bg-danger-50': isRowError(index) }"
                    >
                        <td class="px-2 py-1 text-center text-gray-500">{{ index + 1 }}</td>
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="relative py-1 pl-1.5 pr-1.5"
                        >
                            <slot
                                :name="`cell-${col.key}`"
                                :row="row"
                                :index="index"
                                :disabled="disabled"
                                :duplicate="duplicateIndices.has(index)"
                                :empty="isCellEmpty(index, col.key)"
                            />
                        </td>
                        <td v-if="deletable && !disabled" class="px-1 py-1 text-center">
                            <button
                                type="button"
                                class="rounded p-1 text-gray-400 transition-colors hover:bg-danger-50 hover:text-danger-500"
                                title="Hapus baris"
                                @click="deleteRow(index)"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + (deletable && !disabled ? 2 : 1)" class="px-3 py-4 text-center text-gray-400">
                            Belum ada data
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Footer Actions -->
            <div v-if="!disabled" class="flex gap-1 rounded-b-md border-t border-gray-200 bg-gray-50 px-3 py-1.5">
                <button
                    v-if="addable"
                    type="button"
                    class="rounded p-1.5 transition-colors"
                    :class="canAddRow
                        ? 'text-gray-500 hover:bg-primary-50 hover:text-primary-600'
                        : 'cursor-not-allowed text-gray-300'"
                    :disabled="!canAddRow"
                    title="Tambah baris"
                    @click="addRow"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
                <button
                    v-if="copyable"
                    type="button"
                    class="rounded p-1.5 transition-colors"
                    :disabled="!canAddRow || rows.length === 0"
                    :class="canAddRow && rows.length > 0
                        ? 'text-gray-500 hover:bg-primary-50 hover:text-primary-600'
                        : 'cursor-not-allowed text-gray-300'"
                    title="Duplikat baris terakhir"
                    @click="copyRow"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
                <button
                    v-if="deletable && rows.length > 0"
                    type="button"
                    class="rounded p-1.5 text-gray-500 transition-colors hover:bg-danger-50 hover:text-danger-500"
                    title="Hapus baris terakhir"
                    @click="deleteRow(rows.length - 1)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <p v-if="hasDuplicates && !disabled" class="mt-1 text-sm text-danger-500">
            Terdapat data duplikat
        </p>
        <p v-if="hasEmptyRequired && !disabled" class="mt-1 text-sm text-danger-500">
            Terdapat kolom yang belum diisi
        </p>
        <p v-if="!hasErrors && error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
