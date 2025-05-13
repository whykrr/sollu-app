<template>
    <div class="widget-highlight">
        <div class="widget-icon" :class="color">
            <fa icon="fa-person" class="text-3xl m-auto" />
        </div>
        <div class="widget-content">
            <div class="widget-title">{{ title }}</div>
            <span class="widget-description">{{ description }}</span>
        </div>
    </div>
</template>
<script setup>
defineProps({
    title: String,
    description: String,
    color: String,
});
</script>
