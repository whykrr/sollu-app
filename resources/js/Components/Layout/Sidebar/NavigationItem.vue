<template>
    <a
        v-if="external"
        :href="to"
        class="nav-item"
        :class="{
            active: active,
        }"
        :aria-current="active ? 'page' : undefined"
    >
        <FontAwesomeIcon v-if="icon" :icon="icon" class="w-5 shrink-0" />
        <div class="nav-item-label truncate">
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
        :aria-current="active ? 'page' : undefined"
    >
        <FontAwesomeIcon v-if="icon" :icon="icon" class="w-5 shrink-0" />
        <div class="nav-item-label truncate">
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
