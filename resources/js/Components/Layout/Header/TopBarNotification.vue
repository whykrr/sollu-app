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
import { ref } from 'vue'
import SidebarNotification from '../SidebarNotification/SidebarNotification.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBell } from '@fortawesome/free-regular-svg-icons'
import { useDropdown } from '@/Composable/useDropdown'

const { isOpen: showNotification, toggle: toggleNotification, dropdownRef: panelRef } = useDropdown()

</script>
