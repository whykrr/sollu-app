<template>
    <nav class="sidebar-navigation">
        <div class="navigation-list">
            <template v-for="(sidebar, index) in sidebars" :key="index">
                <div
                    v-if="!sidebar.route"
                    v-can="sidebar.permissions"
                    class="nav-section"
                >
                    <div v-if="sidebar.separator === true" class="py-0.5">
                        <div class="w-full border-t pt-1 text-xs">
                            {{ sidebar.label }}
                        </div>
                    </div>
                    <div v-else class="py-0.5">
                        {{ sidebar.label }}
                    </div>
                </div>
                <NavigationItem
                    v-else-if="!sidebar.items"
                    v-can="sidebar.permissions"
                    :to="
                        route().has(sidebar.route) ? route(sidebar.route) : '#'
                    "
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :active="isActive(sidebar)"
                />
                <NavigationDropdown
                    v-else
                    v-can="sidebar.permissions"
                    to="#"
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :active="isActive(sidebar)"
                >
                    <Link
                        v-for="(submenu, subIndex) in sidebar.items"
                        :key="subIndex"
                        v-can="submenu.permissions"
                        :href="
                            route().has('' + submenu.route)
                                ? route('' + submenu.route)
                                : '#'
                        "
                        class="nav-dropdown-item"
                        :class="{
                            active: isActive(submenu),
                        }"
                    >
                        {{ submenu.label }}
                    </Link>
                </NavigationDropdown>
            </template>
        </div>
    </nav>
</template>
<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import NavigationDropdown from './NavigationDropdown.vue';
import NavigationItem from './NavigationItem.vue';
import { mainSidebars } from '@/Composable/Sidebar/main';
import { settingSidebars } from '@/Composable/Sidebar/setting';
import { useAppStore } from '@/store/app';

const appStore = useAppStore();

const activeMenu = computed(() => {
    const _ = usePage().url;
    return route().current();
});

const normalizeRoute = (name) => {
    return name?.endsWith('.index') ? name.slice(0, -6) : name;
};

const isActive = (menu) => {
    const current = normalizeRoute(activeMenu.value);

    if (menu.items) {
        return menu.items.some((child) =>
            current.startsWith('' + normalizeRoute(child.route)),
        );
    }

    return current.startsWith('' + normalizeRoute(menu.route));
};

const sidebars = computed(() => {
    const url = usePage().url;
    return url.startsWith('/settings') ? settingSidebars : mainSidebars;
});
</script>
