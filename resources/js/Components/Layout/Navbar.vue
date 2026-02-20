<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { BaseButton, BaseSelect } from '@/Components/Form';

const authStore = useAuthStore();
const uiStore = useUiStore();

const showUserMenu = ref(false);

const emit = defineEmits<{
    (e: 'action', action: string): void;
    (e: 'logout'): void;
}>();

const branchOptions = computed(() =>
    uiStore.availableBranches.map(b => ({
        value: b.id,
        label: `${b.code} - ${b.name}`,
    }))
);

const selectedBranchId = computed({
    get: () => uiStore.currentBranch?.id || null,
    set: (value) => {
        if (value) {
            uiStore.switchBranch(value);
        }
    },
});

const handleAction = (action: string) => {
    emit('action', action);
};

const handleLogout = () => {
    showUserMenu.value = false;
    emit('logout');
};

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
};

const closeUserMenu = () => {
    showUserMenu.value = false;
};

onMounted(() => {
    uiStore.fetchBranches().then(() => {
        const savedId = localStorage.getItem('currentBranchId');
        const savedBranch = savedId
            ? uiStore.availableBranches.find(b => String(b.id) === savedId) ?? null
            : null;

        if (savedBranch) {
            uiStore.setCurrentBranch(savedBranch);
        } else if (authStore.user?.branch) {
            uiStore.setCurrentBranch(authStore.user.branch);
        } else if (uiStore.availableBranches.length > 0) {
            uiStore.setCurrentBranch(uiStore.availableBranches[0]);
        }
    });
});
</script>

<template>
    <header
        class="fixed right-0 top-0 z-30 flex h-16 items-center border-b border-gray-200 bg-white transition-all duration-300"
        :class="{
            'left-64': !uiStore.sidebarCollapsed,
            'left-20': uiStore.sidebarCollapsed,
            'left-0 lg:left-64': !uiStore.sidebarCollapsed,
            'left-0 lg:left-20': uiStore.sidebarCollapsed,
        }"
    >
        <div class="flex w-full items-center justify-between px-4">
            <!-- Left side -->
            <div class="flex items-center gap-4">
                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="rounded-md p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
                    @click="uiStore.toggleMobileSidebar"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Sidebar toggle (desktop) -->
                <button
                    type="button"
                    class="hidden rounded-md p-2 text-gray-500 hover:bg-gray-100 lg:block"
                    @click="uiStore.toggleSidebar"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page title -->
                <h1 class="text-lg font-semibold text-gray-900">
                    {{ uiStore.pageTitle }}
                </h1>
            </div>

            <!-- Center - Action buttons -->
            <div class="hidden items-center gap-2 md:flex">
                <template v-for="action in uiStore.pageActions" :key="action.key">
                    <BaseButton
                        v-if="!action.permission || authStore.can(action.action || 'view', action.permission)"
                        :variant="action.variant || 'secondary'"
                        size="sm"
                        @click="handleAction(action.key)"
                    >
                        <!-- Add Icon -->
                        <svg v-if="action.key === 'add'" class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <!-- Edit Icon -->
                        <svg v-else-if="action.key === 'edit'" class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <!-- Delete Icon -->
                        <svg v-else-if="action.key === 'delete'" class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <!-- Approve Icon -->
                        <svg v-else-if="action.key === 'approve'" class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <!-- Print Icon -->
                        <svg v-else-if="action.key === 'print'" class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <!-- Export Icon -->
                        <svg v-else-if="action.key === 'export'" class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ action.label }}
                    </BaseButton>
                </template>
            </div>

            <!-- Right side -->
            <div class="flex items-center gap-4">
                <!-- Branch selector (disabled when a form is open) -->
                <div class="hidden w-48 sm:block">
                    <BaseSelect
                        v-model="selectedBranchId"
                        :options="branchOptions"
                        placeholder="Pilih Cabang"
                        :disabled="uiStore.formIsOpen"
                    />
                </div>

                <!-- User menu -->
                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg p-2 hover:bg-gray-100"
                        @click="toggleUserMenu"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white">
                            {{ authStore.user?.name?.charAt(0).toUpperCase() }}
                        </div>
                        <span class="hidden text-sm font-medium text-gray-700 md:block">
                            {{ authStore.user?.name }}
                        </span>
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown -->
                    <div
                        v-if="showUserMenu"
                        class="absolute right-0 mt-2 w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/5"
                    >
                        <div class="border-b border-gray-100 px-4 py-2">
                            <p class="text-sm font-medium text-gray-900">{{ authStore.user?.name }}</p>
                            <p class="text-xs text-gray-500">{{ authStore.user?.email }}</p>
                        </div>
                        <a
                            href="#"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            @click.prevent="closeUserMenu"
                        >
                            Profil
                        </a>
                        <a
                            href="#"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            @click.prevent="closeUserMenu"
                        >
                            Pengaturan
                        </a>
                        <hr class="my-1 border-gray-100" />
                        <a
                            href="#"
                            class="block px-4 py-2 text-sm text-danger-600 hover:bg-gray-100"
                            @click.prevent="handleLogout"
                        >
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Click outside to close user menu -->
    <div
        v-if="showUserMenu"
        class="fixed inset-0 z-20"
        @click="closeUserMenu"
    />
</template>
