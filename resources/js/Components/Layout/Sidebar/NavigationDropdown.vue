<template>
    <div ref="dropdownRef" class="nav-dropdown" :class="{ active: isSubMenuOpen }">
        <button
            type="button"
            class="nav-item nav-item-dropdown w-full text-left cursor-pointer"
            :class="{ 'font-semibold': active }"
            :aria-expanded="isSubMenuOpen"
            @click="toggleSubMenu"
        >
            <FontAwesomeIcon :icon="icon" class="w-5 shrink-0" />
            <div class="nav-item-label truncate">{{ label }}</div>
            <FontAwesomeIcon :icon="faChevronDown" class="nav-item-caret ml-auto" />
        </button>

        <!-- Submenu List -->
        <div class="nav-dropdown-list">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { faChevronDown } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { onBeforeMount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    to: String,
    icon: Object,
    label: String,
    active: Boolean,
})

const dropdownRef = ref(null)
const isSubMenuOpen = ref(Boolean(props.active))

watch(
    () => props.active,
    val => {
        if (val) {
            isSubMenuOpen.value = true
        }
    }
)

const toggleSubMenu = () => {
    isSubMenuOpen.value = !isSubMenuOpen.value
}

const handleClickOutside = event => {
    // Only close on desktop or click outside if not currently active
    if (!props.active && dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isSubMenuOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeMount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
