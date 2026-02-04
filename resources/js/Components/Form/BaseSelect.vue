<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import type { SelectOption } from '@/types';

interface Props {
    modelValue?: string | number | null;
    options: SelectOption[];
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    label?: string;
    required?: boolean;
    helpText?: string;
    id?: string;
    searchable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    options: () => [],
    placeholder: 'Pilih...',
    searchable: true,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'change', value: string | number | null): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const containerRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const dropdownRef = ref<HTMLElement | null>(null);

const selectedLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === '' || props.modelValue === undefined) {
        return '';
    }
    const option = props.options.find(o => String(o.value) === String(props.modelValue));
    return option?.label || '';
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(o => o.label.toLowerCase().includes(query));
});

const openDropdown = () => {
    if (props.disabled) return;
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = -1;
    nextTick(() => {
        searchInputRef.value?.focus();
    });
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
};

const toggleDropdown = () => {
    if (isOpen.value) {
        closeDropdown();
    } else {
        openDropdown();
    }
};

const selectOption = (option: SelectOption) => {
    if (option.disabled) return;
    emit('update:modelValue', option.value);
    emit('change', option.value);
    closeDropdown();
};

const clearSelection = (e: Event) => {
    e.stopPropagation();
    emit('update:modelValue', null);
    emit('change', null);
};

const handleKeydown = (e: KeyboardEvent) => {
    if (!isOpen.value) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            openDropdown();
        }
        return;
    }

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (highlightedIndex.value < filteredOptions.value.length - 1) {
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
            if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            e.preventDefault();
            closeDropdown();
            break;
    }
};

const scrollToHighlighted = () => {
    nextTick(() => {
        const el = dropdownRef.value?.querySelector(`[data-index="${highlightedIndex.value}"]`);
        el?.scrollIntoView({ block: 'nearest' });
    });
};

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
});

watch(() => searchQuery.value, () => {
    highlightedIndex.value = filteredOptions.value.length > 0 ? 0 : -1;
});

const triggerClasses = computed(() => [
    'relative flex w-full cursor-pointer items-center rounded-md border px-3 py-2 text-sm shadow-sm transition-colors',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    props.error
        ? 'border-danger-500 text-danger-900 focus:border-danger-500 focus:ring-danger-500'
        : isOpen.value
            ? 'border-primary-500 ring-2 ring-primary-500 ring-offset-0'
            : 'border-gray-300 text-gray-900 focus:border-primary-500 focus:ring-primary-500',
    props.disabled ? 'cursor-not-allowed bg-gray-100' : 'bg-white',
]);
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-danger-500">*</span>
        </label>

        <!-- Trigger -->
        <div
            :id="id"
            tabindex="0"
            :class="triggerClasses"
            @click="toggleDropdown"
            @keydown="handleKeydown"
        >
            <span v-if="selectedLabel" class="flex-1 truncate text-gray-900">{{ selectedLabel }}</span>
            <span v-else class="flex-1 truncate text-gray-400">{{ placeholder }}</span>

            <!-- Clear button -->
            <button
                v-if="selectedLabel && !disabled"
                type="button"
                class="mr-1 flex-shrink-0 text-gray-400 hover:text-gray-600"
                tabindex="-1"
                @click="clearSelection"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Chevron -->
            <svg
                class="h-4 w-4 flex-shrink-0 text-gray-400 transition-transform"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            ref="dropdownRef"
            class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg"
        >
            <!-- Search input -->
            <div v-if="searchable" class="border-b border-gray-200 p-2">
                <input
                    ref="searchInputRef"
                    v-model="searchQuery"
                    type="text"
                    class="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    placeholder="Ketik untuk mencari..."
                    @keydown="handleKeydown"
                />
            </div>

            <!-- Options list -->
            <ul class="max-h-60 overflow-auto py-1">
                <li
                    v-for="(option, index) in filteredOptions"
                    :key="option.value"
                    :data-index="index"
                    class="cursor-pointer select-none px-3 py-2 text-sm transition-colors"
                    :class="[
                        option.disabled
                            ? 'cursor-not-allowed text-gray-400'
                            : highlightedIndex === index
                                ? 'bg-primary-50 text-primary-700'
                                : 'text-gray-900 hover:bg-gray-50',
                        String(modelValue) === String(option.value) && !option.disabled
                            ? 'font-medium'
                            : '',
                    ]"
                    @click="selectOption(option)"
                    @mouseenter="highlightedIndex = index"
                >
                    <div class="flex items-center justify-between">
                        <span class="truncate">{{ option.label }}</span>
                        <svg
                            v-if="String(modelValue) === String(option.value)"
                            class="h-4 w-4 text-primary-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </li>
                <li v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                    Tidak ada hasil
                </li>
            </ul>
        </div>

        <p v-if="helpText && !error" class="mt-1 text-sm text-gray-500">
            {{ helpText }}
        </p>
        <p v-if="error" class="mt-1 text-sm text-danger-500">
            {{ error }}
        </p>
    </div>
</template>
