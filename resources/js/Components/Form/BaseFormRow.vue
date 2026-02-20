<script setup lang="ts">
interface Props {
    label?: string;
    required?: boolean;
    error?: string;
    helpText?: string;
    /** Width class for the label column. Default: 'w-28' */
    labelWidth?: string;
}

withDefaults(defineProps<Props>(), {
    labelWidth: 'w-28',
});
</script>

<template>
    <div class="flex min-w-0 items-start gap-x-2">
        <!-- Label column -->
        <div
            class="shrink-0 pt-[7px] text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
            :class="labelWidth"
        >
            {{ label }}<span v-if="required" class="ml-0.5 text-danger-500">*</span>
        </div>

        <!-- Field column -->
        <div class="min-w-0 flex-1">
            <slot />
            <p v-if="helpText && !error" class="mt-0.5 text-xs text-gray-400">{{ helpText }}</p>
            <p v-if="error" class="mt-0.5 text-xs text-danger-500">{{ error }}</p>
        </div>
    </div>
</template>
