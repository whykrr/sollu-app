<template>
  <div
    ref="dropdownRef"
    class="nav-dropdown"
    :class="{ active: active || isSubMenuOpen }"
  >
    <a
      href="#"
      class="nav-item nav-item-dropdown"
      @click.prevent="toggleSubMenu"
    >
      <FontAwesomeIcon :icon="icon" class="w-[20px]" />
      <div class="nav-item-label">{{ label }}</div>
      <FontAwesomeIcon :icon="faChevronDown" class="nav-item-caret" />
    </a>

    <!-- Animated Submenu -->

    <div class="nav-dropdown-list">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { faChevronDown } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { onBeforeMount, onMounted, ref } from 'vue'

const props = defineProps({
    to: String,
    icon: Object,
    label: String,
    active: Boolean,
})

const dropdownRef = ref(null)
const isSubMenuOpen = ref(false)

const toggleSubMenu = () => {
    if (!props.active) {
        isSubMenuOpen.value = !isSubMenuOpen.value
    }
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
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
