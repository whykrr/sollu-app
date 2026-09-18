<template>
    <div class="flex flex-col gap-2 h-full">
        <!-- 1. Header Title & Top Actions -->
        <div v-if="$slots.header" class="flex flex-col gap-2">
            <slot name="header" />
        </div>

        <!-- 2. Widgets / KPI Cards (Berada di antara Header dan Filter) -->
        <div v-if="$slots.widgets">
            <slot name="widgets" />
        </div>

        <!-- 3. Filter / Search Toolbar -->
        <div v-if="$slots.filter || $slots.filters" class="flex flex-col gap-2">
            <slot name="filter" />
            <slot name="filters" />
        </div>

        <!-- 4. Scrollable Container (Table / Main Content) -->
        <div
            class="flex-1 min-h-0 overflow-auto flex flex-col [&::-webkit-scrollbar]:w-0 [&::-webkit-scrollbar]:h-0"
        >
            <slot />
        </div>

        <!-- 5. Footer (Pagination) -->
        <div v-if="$slots.footer" class="">
            <slot name="footer" />
        </div>
    </div>
</template>
<script setup></script>
