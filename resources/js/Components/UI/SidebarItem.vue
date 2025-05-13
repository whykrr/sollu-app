<template>
    <Link
        :href="to"
        class="sidebar-item"
        :class="{ active: isActive, 'rounded-lg': icon }"
    >
        <fa v-if="icon" :icon="icon" class="w-[20px]"></fa>
        <div v-else class="w-[20px]"></div>
        <div>
            {{ name }}
        </div>
    </Link>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    to: String,
    icon: String,
    name: String,
    isActive: Boolean,
});
</script>
