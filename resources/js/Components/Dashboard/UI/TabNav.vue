<template>
    <li role="presentation">
        <button class="btn" type="button" role="tab">
            <FontAwesomeIcon v-if="icon" :icon />
            {{ label }}
        </button>
    </li>
</template>

<script setup>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

defineProps({
    icon: String,
    label: String,
});
</script>
