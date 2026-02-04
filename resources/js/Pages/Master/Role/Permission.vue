<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import { BaseButton, BaseSelect, BaseCheckbox } from '@/Components/Form';
import type { SelectOption } from '@/types';
import axios from 'axios';

interface Props {
    roleId?: string | number;
}

const props = defineProps<Props>();

const uiStore = useUiStore();
const authStore = useAuthStore();

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

// State
const loading = ref(false);
const saving = ref(false);
const selectedRoleId = ref<number | null>(null);
const roleOptions = ref<SelectOption[]>([]);
const allPermissions = ref<any[]>([]);
const rolePermissions = ref<Record<number, any>>({});

const actions = [
    { key: 'can_view', label: 'View' },
    { key: 'can_create', label: 'Create' },
    { key: 'can_edit', label: 'Edit' },
    { key: 'can_delete', label: 'Delete' },
    { key: 'can_approve', label: 'Approve' },
    { key: 'can_print', label: 'Print' },
    { key: 'can_export', label: 'Export' },
];

const fetchRoles = async () => {
    try {
        const response = await axios.get('/api/roles/list');
        roleOptions.value = response.data.data.map((r: any) => ({
            value: r.id,
            label: r.name,
        }));

        // Get role_id from props or URL
        if (props.roleId) {
            selectedRoleId.value = typeof props.roleId === 'string' ? parseInt(props.roleId) : props.roleId;
            fetchRolePermissions();
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            const roleIdParam = urlParams.get('role_id');
            if (roleIdParam) {
                selectedRoleId.value = parseInt(roleIdParam);
                fetchRolePermissions();
            }
        }
    } catch (error) {
        console.error('Failed to fetch roles:', error);
    }
};

const fetchRolePermissions = async () => {
    if (!selectedRoleId.value) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/roles/${selectedRoleId.value}/permissions`);
        allPermissions.value = response.data.data.all_permissions;
        rolePermissions.value = response.data.data.role_permissions;
    } catch (error) {
        console.error('Failed to fetch role permissions:', error);
    } finally {
        loading.value = false;
    }
};

const getPermissionValue = (permissionId: number, action: string): boolean => {
    return rolePermissions.value[permissionId]?.[action] ?? false;
};

const setPermissionValue = (permissionId: number, action: string, value: boolean) => {
    if (!rolePermissions.value[permissionId]) {
        rolePermissions.value[permissionId] = {
            can_view: false,
            can_create: false,
            can_edit: false,
            can_delete: false,
            can_approve: false,
            can_print: false,
            can_export: false,
        };
    }
    rolePermissions.value[permissionId][action] = value;

    // If any action is enabled, enable can_view
    if (value && action !== 'can_view') {
        rolePermissions.value[permissionId].can_view = true;
    }

    // If can_view is disabled, disable all other actions
    if (!value && action === 'can_view') {
        Object.keys(rolePermissions.value[permissionId]).forEach((key) => {
            rolePermissions.value[permissionId][key] = false;
        });
    }
};

const toggleAllForPermission = (permissionId: number, value: boolean) => {
    if (!rolePermissions.value[permissionId]) {
        rolePermissions.value[permissionId] = {};
    }
    actions.forEach((action) => {
        rolePermissions.value[permissionId][action.key] = value;
    });
};

const toggleAllForAction = (action: string, value: boolean) => {
    allPermissions.value.forEach((menu) => {
        setPermissionValue(menu.id, action, value);
        menu.children?.forEach((child: any) => {
            setPermissionValue(child.id, action, value);
        });
    });
};

const isAllCheckedForAction = (action: string): boolean => {
    let allChecked = true;
    allPermissions.value.forEach((menu) => {
        if (!getPermissionValue(menu.id, action)) allChecked = false;
        menu.children?.forEach((child: any) => {
            if (!getPermissionValue(child.id, action)) allChecked = false;
        });
    });
    return allChecked;
};

const handleSave = async () => {
    if (!selectedRoleId.value) return;

    saving.value = true;
    try {
        const permissions = Object.entries(rolePermissions.value).map(([permissionId, actions]) => ({
            permission_id: parseInt(permissionId),
            ...actions,
        }));

        await axios.put(`/api/roles/${selectedRoleId.value}/permissions`, {
            permissions,
        });

        alert('Hak akses berhasil disimpan');
    } catch (error: any) {
        alert(error.response?.data?.message || 'Gagal menyimpan hak akses');
    } finally {
        saving.value = false;
    }
};

const goBack = () => {
    emit('navigate', '/master/role');
};

onMounted(() => {
    uiStore.setPageTitle('Pengaturan Hak Akses');
    uiStore.clearPageActions();
    fetchRoles();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <BaseButton variant="ghost" @click="goBack">
                <svg class="-ml-1 mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </BaseButton>
            <div class="w-64">
                <BaseSelect
                    v-model="selectedRoleId"
                    :options="roleOptions"
                    placeholder="Pilih Role"
                    @change="fetchRolePermissions"
                />
            </div>
            <div class="flex-1"></div>
            <BaseButton
                v-if="selectedRoleId && authStore.can('edit', 'master.permission')"
                variant="primary"
                :loading="saving"
                @click="handleSave"
            >
                Simpan
            </BaseButton>
        </div>

        <!-- Permissions Table -->
        <div v-if="selectedRoleId" class="rounded-lg bg-white shadow">
            <div v-if="loading" class="flex items-center justify-center py-12">
                <svg class="h-8 w-8 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                Menu / Fitur
                            </th>
                            <th
                                v-for="action in actions"
                                :key="action.key"
                                class="px-2 py-3 text-center text-xs font-semibold uppercase text-gray-600"
                            >
                                <div class="flex flex-col items-center gap-1">
                                    <span>{{ action.label }}</span>
                                    <input
                                        type="checkbox"
                                        :checked="isAllCheckedForAction(action.key)"
                                        class="h-3 w-3 rounded border-gray-300 text-primary-600"
                                        @change="toggleAllForAction(action.key, ($event.target as HTMLInputElement).checked)"
                                    />
                                </div>
                            </th>
                            <th class="px-2 py-3 text-center text-xs font-semibold uppercase text-gray-600">
                                Semua
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template v-for="menu in allPermissions" :key="menu.id">
                            <!-- Parent menu -->
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium text-gray-900">
                                    {{ menu.name }}
                                </td>
                                <td
                                    v-for="action in actions"
                                    :key="action.key"
                                    class="px-2 py-2 text-center"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="getPermissionValue(menu.id, action.key)"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600"
                                        @change="setPermissionValue(menu.id, action.key, ($event.target as HTMLInputElement).checked)"
                                    />
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="actions.every(a => getPermissionValue(menu.id, a.key))"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600"
                                        @change="toggleAllForPermission(menu.id, ($event.target as HTMLInputElement).checked)"
                                    />
                                </td>
                            </tr>
                            <!-- Child menus -->
                            <tr v-for="child in menu.children" :key="child.id">
                                <td class="py-2 pl-8 pr-4 text-gray-700">
                                    {{ child.name }}
                                </td>
                                <td
                                    v-for="action in actions"
                                    :key="action.key"
                                    class="px-2 py-2 text-center"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="getPermissionValue(child.id, action.key)"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600"
                                        @change="setPermissionValue(child.id, action.key, ($event.target as HTMLInputElement).checked)"
                                    />
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        :checked="actions.every(a => getPermissionValue(child.id, a.key))"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600"
                                        @change="toggleAllForPermission(child.id, ($event.target as HTMLInputElement).checked)"
                                    />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="rounded-lg bg-white p-12 text-center shadow">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <p class="mt-4 text-gray-500">Pilih role untuk mengatur hak akses</p>
        </div>
    </div>
</template>
