<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import Sidebar from './Sidebar.vue';
import Navbar from './Navbar.vue';

const authStore = useAuthStore();
const uiStore = useUiStore();

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
    (e: 'action', action: string): void;
    (e: 'logout'): void;
}>();

const handleNavigate = (route: string) => {
    emit('navigate', route);
};

const handleAction = (action: string) => {
    emit('action', action);
};

const handleLogout = async () => {
    await authStore.logout();
    emit('logout');
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <Sidebar @navigate="handleNavigate" />
        <Navbar @action="handleAction" @logout="handleLogout" />

        <!-- Main content -->
        <main
            class="pt-16 transition-all duration-300"
            :class="{
                'lg:pl-64': !uiStore.sidebarCollapsed,
                'lg:pl-20': uiStore.sidebarCollapsed,
            }"
        >
            <div class="p-4 lg:p-6">
                <slot />
            </div>
        </main>
    </div>
</template>
