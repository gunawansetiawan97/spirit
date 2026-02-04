<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import type { ActionConfig } from '@/types';

interface Props {
    row: any;
    actions: ActionConfig[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'view', row: any): void;
    (e: 'edit', row: any): void;
    (e: 'delete', row: any): void;
    (e: 'permissions', row: any): void;
    (e: 'custom', row: any, action: ActionConfig): void;
}>();

const authStore = useAuthStore();

const actionIcons: Record<string, string> = {
    view: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`,
    edit: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />`,
    delete: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />`,
    permissions: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />`,
};

const actionColors: Record<string, string> = {
    view: 'bg-primary-600 hover:bg-primary-700',
    edit: 'bg-warning-500 hover:bg-warning-600',
    delete: 'bg-danger-600 hover:bg-danger-700',
    permissions: 'bg-purple-600 hover:bg-purple-700',
};

const actionLabels: Record<string, string> = {
    view: 'Lihat',
    edit: 'Edit',
    delete: 'Hapus',
    permissions: 'Hak Akses',
};

const visibleActions = computed(() => {
    return props.actions.filter((action) => {
        // Check permission
        if (action.permission) {
            const actionType = action.action || 'view';
            if (!authStore.can(actionType, action.permission)) {
                return false;
            }
        }
        // Check custom show function
        if (action.show && !action.show(props.row)) {
            return false;
        }
        return true;
    });
});

const getIcon = (action: ActionConfig) => {
    return action.icon || actionIcons[action.type] || '';
};

const getColor = (action: ActionConfig) => {
    return action.color || actionColors[action.type] || 'bg-gray-500 hover:bg-gray-600';
};

const getLabel = (action: ActionConfig) => {
    return action.label || actionLabels[action.type] || action.type;
};

const handleClick = (action: ActionConfig) => {
    if (action.onClick) {
        action.onClick(props.row);
    } else {
        switch (action.type) {
            case 'view':
                emit('view', props.row);
                break;
            case 'edit':
                emit('edit', props.row);
                break;
            case 'delete':
                emit('delete', props.row);
                break;
            case 'permissions':
                emit('permissions', props.row);
                break;
            default:
                emit('custom', props.row, action);
        }
    }
};
</script>

<template>
    <div class="flex justify-end gap-2">
        <button
            v-for="(action, index) in visibleActions"
            :key="index"
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-white transition-colors"
            :class="getColor(action)"
            :title="getLabel(action)"
            @click.stop="handleClick(action)"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getIcon(action)" />
        </button>
    </div>
</template>
