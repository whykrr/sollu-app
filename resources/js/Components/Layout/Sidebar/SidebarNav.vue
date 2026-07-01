<template>
    <nav class="sidebar-navigation">
        <div class="navigation-list">
            <NavigationNode
                v-for="(sidebar, index) in sidebars"
                :key="index"
                :item="sidebar"
                :is-active="isActive"
            />
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { mainSidebars } from '@/Composable/Sidebar/main';
import { settingSidebars } from '@/Composable/Sidebar/setting';
import NavigationNode from './NavigationNode.vue';

const activeMenu = computed(() => {
    // Mengakses usePage().url mendaftarkan dependency ini pada Vue's reactivity system.
    // Tanpa ini, route().current() dari Ziggy tidak akan trigger re-render saat Inertia pindah halaman.
    const _ = usePage().url;
    return route().current();
});

const normalizeRoute = (name) => {
    return name?.endsWith('.index') ? name.slice(0, -6) : name;
};

const normalizeActiveRoute = (name) => {
    return name?.endsWith('.') ? name.slice(0, -1) : name;
};

const isActive = (menu) => {
    const current = normalizeRoute(activeMenu.value);
    
    if (!current) return false;

    // Prioritize explicit activeRoute property
    if (menu.activeRoute) {
        const target = normalizeActiveRoute(menu.activeRoute);
        if (target && current.startsWith(target)) return true;
    }

    // Fallback logic for dropdowns if activeRoute somehow doesn't match
    if (menu.items) {
        return menu.items.some((child) => {
            if (child.activeRoute) {
                const target = normalizeActiveRoute(child.activeRoute);
                if (target && current.startsWith(target)) return true;
            }
            return current.startsWith('' + normalizeRoute(child.route));
        });
    }

    return current.startsWith('' + normalizeRoute(menu.route));
};

const sidebars = computed(() => {
    const url = usePage().url;
    return url.startsWith('/settings') ? settingSidebars : mainSidebars;
});
</script>
