<template>
  <nav class="sidebar-navigation">
    <div class="navigation-list">
      <template v-for="(sidebar, index) in sidebars" :key="index">
        <div
          v-show="appStore.sidebar.show"
          v-if="!sidebar.route"
          v-can="sidebar.permissions"
          class="nav=section"
        >
          {{ sidebar.label }}
        </div>
        <NavigationItem
          v-else-if="!sidebar.items"
          v-can="sidebar.permissions"
          :to="
            route().has('dashboard.' + sidebar.route)
              ? route('dashboard.' + sidebar.route)
              : '#'
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
              route().has('dashboard.' + submenu.route)
                ? route('dashboard.' + submenu.route)
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
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import NavigationDropdown from './NavigationDropdown.vue'
import NavigationItem from './NavigationItem.vue'
import { mainSidebars } from '@/helpers/Dashboard/Sidebar/main'
import { settingSidebars } from '@/helpers/Dashboard/Sidebar/setting'
import { useAppStore } from '@/store/Dashboard/app'

const appStore = useAppStore()

const activeMenu = computed(() => {
    const _ = usePage().url
    return route().current()
})

const normalizeRoute = (name) => {
    return name?.endsWith('.index') ? name.slice(0, -6) : name
}

const isActive = (menu) => {
    const current = normalizeRoute(activeMenu.value)

    if (menu.items) {
        return menu.items.some((child) =>
            current.startsWith('dashboard.' + normalizeRoute(child.route)),
        )
    }

    return current.startsWith('dashboard.' + normalizeRoute(menu.route))
}

const sidebars = computed(() => {
    const url = usePage().url
    return url.startsWith('/merchant') ? settingSidebars : mainSidebars
})
</script>
