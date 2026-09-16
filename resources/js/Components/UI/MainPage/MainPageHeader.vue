<template>
    <div>
        <div
            class="flex flex-col sm:flex-row gap-2 sm:items-center justify-between -mt-1"
        >
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">
                {{ title }}
            </h2>
            <div
                v-if="$slots.default"
                class="flex flex-wrap items-center gap-2 w-full sm:w-auto"
            >
                <slot></slot>
            </div>
        </div>
        <p v-if="description" class="text-sm text-gray-500">
            {{ description }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    description: String,
});
</script>
