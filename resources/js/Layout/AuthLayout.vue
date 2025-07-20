<template>
    <div class="flex h-screen">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
        >
            <div class="spinner"></div>
        </div>
        <div
            class="h-screen w-full grid grid-cols-12 gap-4 p-6 bg-radial-[at_25%_50%] from-light to-white"
        >
            <div class="col-span-6 max-h-full min-h-full">
                <AuthBanner />
            </div>
            <div class="col-span-6 mx-36">
                <slot />
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AuthBanner from "@/Components/AuthBanner.vue";

const loading = ref(false);

// Event listener for Inertia start/finish
router.on("start", () => (loading.value = true));
router.on("finish", () => (loading.value = false));
</script>
