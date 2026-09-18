<template>
    <div class="flex flex-row items-center justify-between gap-2 w-full -mt-1">
        <div class="min-w-0 flex-1">
            <h2
                class="text-lg sm:text-xl md:text-2xl font-bold text-neutral-900 truncate leading-tight"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="hidden sm:block text-xs sm:text-sm text-neutral-500 truncate leading-tight mt-0.5"
            >
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.default" class="shrink-0 flex items-center gap-1.5 sm:gap-2 justify-end">
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    description: String,
})
</script>
