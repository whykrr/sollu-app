<template>
    <div
        class="min-h-screen flex items-center justify-center bg-indigo-50/60 px-4 sm:px-6 lg:px-8 relative overflow-hidden select-none"
    >
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
        >
            <div
                class="w-10 h-10 border-4 border-l-indigo-600 border-b-indigo-400 border-r-indigo-200 border-t-transparent rounded-full animate-spin"
            />
        </div>

        <!-- Background Elements -->
        <div
            class="absolute -top-24 -left-24 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"
        ></div>
        <div
            class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-400/15 rounded-full blur-3xl pointer-events-none"
        ></div>

        <slot />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const loading = ref(false)

router.on('start', () => (loading.value = true))
router.on('finish', () => (loading.value = false))
</script>
