<template>
    <div class="flex flex-row gap-2">
        <h2 class="text-2xl font-bold">{{ title }}</h2>
        <div class="flex-1 border-slate-200"></div>
        <div class="space-x-2 items-end flex">
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
});
</script>
