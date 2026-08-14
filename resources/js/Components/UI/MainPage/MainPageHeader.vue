<template>
    <div class="flex flex-col sm:flex-row gap-2 sm:items-center justify-between">
        <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">{{ title }}</h2>
        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
});
</script>
