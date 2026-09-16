<template>
    <a
        v-if="external"
        :href="to"
        class="nav-item"
        :class="{
            active: active,
        }"
    >
        <FontAwesomeIcon v-if="icon" :icon="icon" class="w-5" />
        <div class="nav-item-label">
            {{ label }}
        </div>
    </a>
    <Link
        v-else
        :href="to"
        class="nav-item"
        :class="{
            active: active,
        }"
    >
        <FontAwesomeIcon v-if="icon" :icon="icon" class="w-5" />
        <div class="nav-item-label">
            {{ label }}
        </div>
    </Link>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link } from '@inertiajs/vue3'

defineProps({
    to: String,
    icon: Object,
    label: String,
    active: Boolean,
    external: Boolean,
})
</script>
