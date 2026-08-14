<template>
  <div class="h-screen h-dvh w-full overflow-hidden flex flex-col justify-center bg-white lg:bg-neutral-lighter select-none">
    <div
      v-if="loading"
      class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
    >
      <div class="spinner" />
    </div>
    <div
      class="h-full w-full grid grid-cols-1 lg:grid-cols-12 gap-0 lg:gap-6 p-0 lg:p-6"
    >
      <div class="hidden lg:flex lg:col-span-6 h-full max-h-full rounded-2xl overflow-hidden shadow-md">
        <AuthBanner />
      </div>
      <div class="w-full lg:col-span-6 h-full max-h-full flex flex-col justify-center">
        <div class="bg-white lg:rounded-2xl p-6 sm:p-10 lg:p-8 xl:px-14 xl:py-10 h-full max-h-full flex flex-col justify-between lg:shadow-xl lg:border lg:border-slate-100">
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
