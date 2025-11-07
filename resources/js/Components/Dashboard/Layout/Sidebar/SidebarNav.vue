<template>
    <nav class="sidebar-navigation">
        <div class="navigation-list">
            <template v-for="sidebar in sidebars">
                <div
                    v-show="appStore.sidebar.show"
                    v-if="!sidebar.route"
                    class="nav=section"
                    v-can="sidebar.permissions"
                >
                    {{ sidebar.label }}
                </div>
                <NavigationItem
                    v-else-if="!sidebar.items"
                    :to="
                        route().has('dashboard.' + sidebar.route)
                            ? route('dashboard.' + sidebar.route)
                            : '#'
                    "
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :active="isActive(sidebar)"
                    v-can="sidebar.permissions"
                />
                <NavigationDropdown
                    v-else
                    to="#"
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :active="isActive(sidebar)"
                    v-can="sidebar.permissions"
                >
                    <Link
                        v-for="submenu in sidebar.items"
                        :href="
                            route().has('dashboard.' + submenu.route)
                                ? route('dashboard.' + submenu.route)
                                : '#'
                        "
                        class="nav-dropdown-item"
                        :class="{
                            active: isActive(submenu),
                        }"
                        v-can="submenu.permissions"
                    >
                        {{ submenu.label }}
                    </Link>
                </NavigationDropdown>
            </template>
        </div>
    </nav>
</template>
<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import NavigationDropdown from "./NavigationDropdown.vue";
import NavigationItem from "./NavigationItem.vue";
import { mainSidebars } from "@/helpers/Dashboard/Sidebar/main";
import { settingSidebars } from "@/helpers/Dashboard/Sidebar/setting";
import { useAppStore } from "@/store/Dashboard/app";

const appStore = useAppStore();

const activeMenu = computed(() => {
    const _ = usePage().url;
    return route().current();
});

const normalizeRoute = (name) => {
    return name?.endsWith(".index") ? name.slice(0, -6) : name;
};

const isActive = (menu) => {
    const current = normalizeRoute(activeMenu.value);

    if (menu.items) {
        return menu.items.some((child) =>
            current.startsWith("dashboard." + normalizeRoute(child.route))
        );
    }

    return current.startsWith("dashboard." + normalizeRoute(menu.route));
};

const sidebars = computed(() => {
    const url = usePage().url;
    return url.startsWith("/merchant") ? settingSidebars : mainSidebars;
});
</script>
