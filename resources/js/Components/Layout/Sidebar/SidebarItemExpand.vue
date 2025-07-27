<template>
    <div
        class="sidebar-item-expand rounded-lg w-full"
        :class="{ active: isActive || isSubMenuOpen }"
    >
        <a class="expand-toggle" href="#" @click="toggleSubMenu">
            <fa :icon="icon" class="w-[20px]"></fa>
            <div class="grow text-left text-sm">{{ label }}</div>
            <fa
                icon="fa-chevron-down"
                class="transition-transform duration-200 text-sm"
                :class="{ 'rotate-180': isSubMenuOpen }"
            />
        </a>

        <!-- Animated Submenu -->
        <transition name="submenu">
            <div v-if="isSubMenuOpen" class="sidebar-expand-container gap-2">
                <slot></slot>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, toRef, watch } from "vue";

const props = defineProps({
    to: String,
    icon: String,
    label: String,
    isActive: Boolean,
});

const isSubMenuOpen = toRef(props.isActive);

watch(
    () => props.isActive,
    (newValue) => {
        isSubMenuOpen.value = newValue;
    }
);

const toggleSubMenu = () => {
    isSubMenuOpen.value = !isSubMenuOpen.value;
};
</script>
