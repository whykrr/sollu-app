<template>
    <nav class="flex-1 sidebar">
        <div class="sidebar-container">
            <template v-for="sidebar in sidebars">
                <div
                    v-if="!sidebar.route"
                    class="text-sm px-2 select-none mt-1.5 text-neutral-400 font-medium"
                    v-can="sidebar.permissions"
                >
                    {{ sidebar.label }}
                </div>
                <SidebarItem
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
                <SidebarItemExpand
                    v-else
                    to="#"
                    :icon="sidebar.icon"
                    :label="sidebar.label"
                    :active="isActive(sidebar)"
                    v-can="sidebar.permissions"
                >
                    <template v-for="item in sidebar.items">
                        <SidebarItem
                            :to="
                                route().has('dashboard.' + item.route)
                                    ? route('dashboard.' + item.route)
                                    : '#'
                            "
                            :icon="item.icon"
                            :label="item.label"
                            :active="isActive(item)"
                            v-can="sidebar.permissions"
                        />
                    </template>
                </SidebarItemExpand>
            </template>
        </div>
    </nav>
</template>
<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SidebarItemExpand from "./SidebarItemExpand.vue";
import SidebarItem from "./SidebarItem.vue";
import { mainSidebars } from "@/helpers/Dashboard/Sidebar/main";
import { settingSidebars } from "@/helpers/Dashboard/Sidebar/setting";

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
