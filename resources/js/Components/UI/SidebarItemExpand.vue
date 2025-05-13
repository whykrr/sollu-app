<template>
    <div
        class="sidebar-item-expand rounded-lg w-full"
        :class="{ active: isActive || isSubMenuOpen }"
    >
        <button type="button" @click="toggleSubMenu">
            <fa :icon="icon" class="w-[20px]"></fa>
            <div class="grow text-left">{{ name }}</div>
            <fa
                icon="fa-chevron-down"
                class="transition-transform duration-200"
                :class="{ 'rotate-180': isSubMenuOpen }"
            />
        </button>

        <!-- Animated Submenu -->
        <transition name="submenu">
            <div v-if="isSubMenuOpen" class="gap-2">
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
    name: String,
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
