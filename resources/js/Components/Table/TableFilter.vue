<script setup lang="ts">
import { ref, watch } from 'vue';
import { BaseInput, BaseSelect, BaseButton } from '../Form';
import type { FilterField } from '@/types';

interface Props {
    fields: FilterField[];
    modelValue: Record<string, any>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Record<string, any>): void;
    (e: 'search'): void;
    (e: 'reset'): void;
}>();

const localFilters = ref<Record<string, any>>({ ...props.modelValue });

watch(
    () => props.modelValue,
    (newVal) => {
        localFilters.value = { ...newVal };
    },
    { deep: true }
);

const updateFilter = (key: string, value: any) => {
    localFilters.value[key] = value;
};

const handleSearch = () => {
    emit('update:modelValue', { ...localFilters.value });
    emit('search');
};

const handleReset = () => {
    const resetFilters: Record<string, any> = {};
    props.fields.forEach((field) => {
        resetFilters[field.key] = '';
    });
    localFilters.value = resetFilters;
    emit('update:modelValue', resetFilters);
    emit('reset');
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <div v-for="field in fields" :key="field.key">
                <BaseInput
                    v-if="field.type === 'text'"
                    :model-value="localFilters[field.key] || ''"
                    :label="field.label"
                    :placeholder="field.placeholder || `Cari ${field.label.toLowerCase()}...`"
                    @update:model-value="updateFilter(field.key, $event)"
                    @keyup.enter="handleSearch"
                />
                <BaseSelect
                    v-else-if="field.type === 'select'"
                    :model-value="localFilters[field.key] || ''"
                    :label="field.label"
                    :options="field.options || []"
                    :placeholder="field.placeholder || `Pilih ${field.label.toLowerCase()}...`"
                    @update:model-value="updateFilter(field.key, $event)"
                />
                <BaseInput
                    v-else-if="field.type === 'date'"
                    :model-value="localFilters[field.key] || ''"
                    type="date"
                    :label="field.label"
                    @update:model-value="updateFilter(field.key, $event)"
                />
            </div>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <BaseButton variant="secondary" @click="handleReset">
                Reset
            </BaseButton>
            <BaseButton variant="primary" @click="handleSearch">
                <svg class="-ml-1 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Cari
            </BaseButton>
        </div>
    </div>
</template>
