<template>
    <li role="presentation">
        <button class="btn" type="button" role="tab">
            <fa v-if="icon" :icon />
            {{ label }}
        </button>
    </li>
</template>

<script setup>
defineProps({
    icon: String,
    label: String,
});
</script>
