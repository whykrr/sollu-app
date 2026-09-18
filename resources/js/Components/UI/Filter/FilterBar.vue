<template>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2">
        <!-- Left / Main Filters Area -->
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-0">
            <slot name="left">
                <slot />
            </slot>
        </div>

        <!-- Right Area: Actions & Search -->
        <div class="flex flex-wrap items-center gap-2 justify-end shrink-0">
            <slot name="actions" />
            <slot name="search" />
            <slot name="right" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
})
</script>
