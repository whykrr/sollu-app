<template>
    <div class="widget-progress">
        <div class="widget-header">{{ valueString }}</div>
        <div>
            <div class="widget-title">{{ title }}</div>
            <div class="widget-bar">
                <div class="widget-value" :style="widthProgress"></div>
            </div>
            <div class="widget-helper">{{ helperText }}</div>
        </div>
    </div>
</template>
<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    title: String,
    valueString: String,
    value: Number,
    maxValue: Number,
    helperText: String,
});

const widthProgress = computed(() => {
    const progress = ref(Math.round((props.value / props.maxValue) * 100));

    if (progress.value > 100) {
        progress.value = 100;
    }
    return `width: ${progress.value}%;`;
});
</script>
