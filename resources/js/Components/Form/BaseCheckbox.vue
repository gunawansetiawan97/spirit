<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue?: boolean;
    label?: string;
    disabled?: boolean;
    id?: string;
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'change', value: boolean): void;
}>();

const checked = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value);
        emit('change', value);
    },
});
</script>

<template>
    <div class="flex items-start">
        <div class="flex h-5 items-center">
            <input
                :id="id"
                v-model="checked"
                type="checkbox"
                :disabled="disabled"
                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
            />
        </div>
        <div v-if="label" class="ml-2 text-sm">
            <label :for="id" class="font-medium text-gray-700" :class="{ 'cursor-not-allowed opacity-50': disabled }">
                {{ label }}
            </label>
        </div>
    </div>
    <p v-if="error" class="mt-1 text-sm text-danger-500">
        {{ error }}
    </p>
</template>
