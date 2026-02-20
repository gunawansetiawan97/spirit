<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';

const authStore = useAuthStore();
const uiStore = useUiStore();

const expandedMenus = ref<string[]>([]);

const toggleMenu = (code: string) => {
    const index = expandedMenus.value.indexOf(code);
    if (index === -1) {
        expandedMenus.value.push(code);
    } else {
        expandedMenus.value.splice(index, 1);
    }
};

const isExpanded = (code: string) => expandedMenus.value.includes(code);

const isActive = (route: string | null) => {
    if (!route) return false;
    return uiStore.currentRoute === route;
};

const hasActiveChild = (children: any[] | undefined): boolean => {
    if (!children) return false;
    return children.some(child => isActive(child.route) || hasActiveChild(child.children));
};

const emit = defineEmits<{
    (e: 'navigate', route: string): void;
}>();

const handleNavigate = (route: string | null) => {
    if (route) {
        emit('navigate', route);
        uiStore.closeMobileSidebar();
    }
};

// Icon components mapping
const getIcon = (iconName: string | null) => {
    return iconName || 'MenuIcon';
};
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gray-900 transition-transform duration-300 lg:translate-x-0"
        :class="{
            '-translate-x-full': !uiStore.sidebarMobileOpen,
            'lg:w-20': uiStore.sidebarCollapsed,
        }"
    >
        <!-- Logo -->
        <div class="flex h-16 items-center justify-center border-b border-gray-800 px-4">
            <template v-if="!uiStore.sidebarCollapsed">
                <span class="text-xl font-bold text-white">Spirit ERP</span>
            </template>
            <template v-else>
                <span class="text-xl font-bold text-white">S</span>
            </template>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
                <li v-for="menu in authStore.menus" :key="menu.code">
                    <!-- Menu with children -->
                    <template v-if="menu.children && menu.children.length > 0">
                        <button
                            type="button"
                            class="flex w-full items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-800 hover:text-white"
                            :class="{
                                'bg-gray-800 text-white': hasActiveChild(menu.children),
                                'justify-center': uiStore.sidebarCollapsed,
                            }"
                            @click="toggleMenu(menu.code)"
                        >
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <template v-if="!uiStore.sidebarCollapsed">
                                <span class="ml-3 flex-1 text-left">{{ menu.name }}</span>
                                <svg
                                    class="h-4 w-4 transition-transform"
                                    :class="{ 'rotate-180': isExpanded(menu.code) }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </template>
                        </button>
                        <!-- Level 2 submenu -->
                        <ul
                            v-if="!uiStore.sidebarCollapsed"
                            v-show="isExpanded(menu.code)"
                            class="mt-1 space-y-1 pl-8"
                        >
                            <li v-for="child in menu.children" :key="child.code">
                                <!-- Child with grandchildren (level 3 group) -->
                                <template v-if="child.children && child.children.length > 0">
                                    <button
                                        type="button"
                                        class="flex w-full items-center rounded-lg px-3 py-2 text-sm text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
                                        :class="{ 'text-white': hasActiveChild(child.children) }"
                                        @click="toggleMenu(child.code)"
                                    >
                                        <span class="flex-1 text-left">{{ child.name }}</span>
                                        <svg
                                            class="h-3.5 w-3.5 transition-transform"
                                            :class="{ 'rotate-180': isExpanded(child.code) }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <!-- Level 3 submenu -->
                                    <ul
                                        v-show="isExpanded(child.code)"
                                        class="mt-1 space-y-1 pl-4"
                                    >
                                        <li v-for="grandchild in child.children" :key="grandchild.code">
                                            <a
                                                href="#"
                                                class="block rounded-lg px-3 py-2 text-sm text-gray-500 transition-colors hover:bg-gray-800 hover:text-white"
                                                :class="{ 'bg-primary-600 text-white': isActive(grandchild.route) }"
                                                @click.prevent="handleNavigate(grandchild.route)"
                                            >
                                                {{ grandchild.name }}
                                            </a>
                                        </li>
                                    </ul>
                                </template>

                                <!-- Child without grandchildren (leaf item) -->
                                <template v-else>
                                    <a
                                        href="#"
                                        class="block rounded-lg px-3 py-2 text-sm text-gray-400 transition-colors hover:bg-gray-800 hover:text-white"
                                        :class="{ 'bg-primary-600 text-white': isActive(child.route) }"
                                        @click.prevent="handleNavigate(child.route)"
                                    >
                                        {{ child.name }}
                                    </a>
                                </template>
                            </li>
                        </ul>
                    </template>

                    <!-- Menu without children -->
                    <template v-else>
                        <a
                            href="#"
                            class="flex items-center rounded-lg px-3 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-800 hover:text-white"
                            :class="{
                                'bg-primary-600 text-white': isActive(menu.route),
                                'justify-center': uiStore.sidebarCollapsed,
                            }"
                            @click.prevent="handleNavigate(menu.route)"
                        >
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span v-if="!uiStore.sidebarCollapsed" class="ml-3">{{ menu.name }}</span>
                        </a>
                    </template>
                </li>
            </ul>
        </nav>

        <!-- User info -->
        <div class="border-t border-gray-800 p-4">
            <div class="flex items-center" :class="{ 'justify-center': uiStore.sidebarCollapsed }">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-medium text-white">
                    {{ authStore.user?.name?.charAt(0).toUpperCase() }}
                </div>
                <div v-if="!uiStore.sidebarCollapsed" class="ml-3">
                    <p class="text-sm font-medium text-white">{{ authStore.user?.name }}</p>
                    <p class="text-xs text-gray-400">{{ authStore.currentRole?.name }}</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div
        v-if="uiStore.sidebarMobileOpen"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        @click="uiStore.closeMobileSidebar"
    />
</template>
