<template>
  <div ref="panelRef" class="relative">
    <a
      href="#"
      class="nav-icon"
      title="Notifikasi"
      @click.prevent="toggleNotification"
    >
      <FontAwesomeIcon :icon="faBell" />
      <span />
    </a>
    <SidebarNotification
      :is-open="showNotification"
      @close="showNotification = false"
    />
  </div>
</template>
<script setup>
import { onBeforeMount, onMounted, ref } from 'vue'
import SidebarNotification from '../SidebarNotification/SidebarNotification.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBell } from '@fortawesome/free-regular-svg-icons'

const showNotification = ref(false)
const panelRef = ref(null)

const toggleNotification = () => {
    showNotification.value = !showNotification.value
}

const handleClickOutside = (event) => {
    if (panelRef.value && !panelRef.value.contains(event.target)) {
        showNotification.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeMount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
