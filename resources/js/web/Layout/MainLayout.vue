<template>
    <Head>
        <title>{{ settings.name }}</title>
        <link rel="icon" :href="'/storage/' + settings.icon" />
        <meta name="keywords" :content="settings.name" />
        <meta name="description" content="Free Web tutorials" />
    </Head>

    <div class="flex flex-col w-full min-h-full bg-white">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100 bg-opacity-50 z-50"
        >
            <div class="spinner"></div>
        </div>
        <!-- Info -->

        <!-- Navigation -->

        <!-- SLOT -->
        <div class="text-gray-700 grow">
            <slot></slot>
        </div>

        <!-- Footer -->
    </div>
</template>

<script setup>
import { usePage, Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const loading = ref(false);

// Event listener for Inertia start/finish
router.on("start", () => (loading.value = true));
router.on("finish", () => (loading.value = false));

const page = usePage();
const settings = page.props.settings;
</script>
