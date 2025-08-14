<template>
    <Link
        :href="to"
        class="sidebar-item"
        :class="{ active: active, 'rounded-lg': icon }"
    >
        <FontAwesomeIcon
            v-if="icon"
            :icon="icon"
            class="w-[20px]"
        ></FontAwesomeIcon>
        <div v-else class="w-[20px]"></div>
        <div class="text-sm">
            {{ label }}
        </div>
    </Link>
</template>

<script setup>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link } from "@inertiajs/vue3";

defineProps({
    to: String,
    icon: Object,
    label: String,
    active: Boolean,
});
</script>
