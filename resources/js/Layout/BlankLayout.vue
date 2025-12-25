<template>
  <div class="flex h-screen">
    <div
      v-if="loading"
      class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
    >
      <div class="spinner" />
    </div>
    <slot />
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const loading = ref(false)

// Event listener for Inertia start/finish
router.on('start', () => (loading.value = true))
router.on('finish', () => (loading.value = false))
</script>
