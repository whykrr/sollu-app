<template>
  <div class="flex min-h-screen bg-neutral-lighter">
    <div
      v-if="loading"
      class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
    >
      <div class="spinner" />
    </div>
    <div
      class="min-h-screen w-full grid grid-cols-1 lg:grid-cols-12 gap-4 p-3 sm:p-4 bg-neutral-lighter items-center justify-center"
    >
      <div class="hidden lg:block lg:col-span-6 h-full max-h-full">
        <AuthBanner />
      </div>
      <div class="w-full lg:col-span-6 h-full flex flex-col justify-center">
        <div class="bg-white rounded-xl p-6 sm:p-10 lg:px-16 xl:px-24 py-8 lg:py-12 h-full flex flex-col justify-center shadow-xl sm:shadow-2xl border border-slate-100">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthBanner from '@/Components/AuthBanner.vue'

const loading = ref(false)

// Event listener for Inertia start/finish
router.on('start', () => (loading.value = true))
router.on('finish', () => (loading.value = false))
</script>
