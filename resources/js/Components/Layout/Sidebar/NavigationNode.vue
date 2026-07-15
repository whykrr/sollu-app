<template>
    <div>
        <NavigationSection
            v-if="item.type === 'section'"
            v-can="item.permissions"
            :label="item.label"
            :separator="item.separator"
        />

        <NavigationItem
            v-else-if="item.type === 'item'"
            v-can="item.permissions"
            :to="item.url"
            :icon="item.icon"
            :label="item.label"
            :active="isActive(item)"
        />

        <NavigationDropdown
            v-else-if="item.type === 'dropdown'"
            v-can="item.permissions"
            to="#"
            :icon="item.icon"
            :label="item.label"
            :active="isActive(item)"
        >
            <Link
                v-for="(submenu, subIndex) in item.items"
                :key="subIndex"
                v-can="submenu.permissions"
                :href="submenu.url"
                class="nav-dropdown-item"
                :class="{ active: isActive(submenu) }"
            >
                {{ submenu.label }}
            </Link>
        </NavigationDropdown>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import NavigationSection from './NavigationSection.vue';
import NavigationItem from './NavigationItem.vue';
import NavigationDropdown from './NavigationDropdown.vue';

defineProps({
    item: {
        type: Object,
        required: true,
    },
    isActive: {
        type: Function,
        required: true,
    },
});
</script>
