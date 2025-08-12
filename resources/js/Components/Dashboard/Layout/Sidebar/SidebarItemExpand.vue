<template>
    <div
        class="sidebar-item-expand rounded-lg w-full"
        :class="{ active: active || isSubMenuOpen }"
        ref="dropdownRef"
    >
        <a class="expand-toggle" href="#" @click.prevent="toggleSubMenu">
            <FontAwesomeIcon :icon="icon" class="w-[20px]"></FontAwesomeIcon>
            <div class="grow text-left text-sm">{{ label }}</div>
            <FontAwesomeIcon
                :icon="faChevronDown"
                class="transition-transform duration-200 text-sm"
                :class="{ 'rotate-180': isSubMenuOpen }"
            />
        </a>

        <!-- Animated Submenu -->
        <transition name="submenu" mode="out-in">
            <div
                v-if="active || isSubMenuOpen"
                class="sidebar-expand-container gap-2"
            >
                <slot></slot>
            </div>
        </transition>
    </div>
</template>

<script setup>
import {
    faChevronCircleDown,
    faChevronDown,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { onBeforeMount, onMounted, ref } from "vue";

const props = defineProps({
    to: String,
    icon: String,
    label: String,
    active: Boolean,
});

const dropdownRef = ref(null);
const isSubMenuOpen = ref(false);

const toggleSubMenu = () => {
    if (!props.active) {
        isSubMenuOpen.value = !isSubMenuOpen.value;
    }
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isSubMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeMount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>
