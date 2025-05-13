<template>
    <div class="widget">
        <div class="relative">
            <div class="absolute opacity-50 -bottom-8 -left-8">
                <fa class="size-20" :icon="icon" />
            </div>
        </div>
        <div class="text-right text-gray-8">
            <h2 class="text-lg font-semibold">{{ title }}</h2>
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
defineProps({
    icon: String,
    title: String,
});
</script>
