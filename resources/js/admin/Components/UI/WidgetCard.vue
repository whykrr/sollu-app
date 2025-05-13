<template>
    <div class="widget-card">
        <div class="widget-icon">
            <fa :icon class="text-3xl" />
        </div>
        <div class="widget-container">
            <div class="widget-title">{{ title }}</div>
            <div class="widget-subtitle">{{ subTitle }}</div>
            <div class="widget-description">
                {{ description }}
            </div>
        </div>
    </div>
</template>
<script setup>
import { Title } from "chart.js";

defineProps({
    icon: String,
    title: String,
    subTitle: String,
    description: String,
});
</script>
