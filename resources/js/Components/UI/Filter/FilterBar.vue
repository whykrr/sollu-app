<template>
    <ActionBar :compact="compact">
        <template v-if="$slots.filters || $slots.left || $slots.default" #filters>
            <slot name="filters">
                <slot name="left">
                    <slot />
                </slot>
            </slot>
        </template>
        <template v-if="$slots.search" #search>
            <slot name="search" />
        </template>
        <template v-if="$slots.tools || $slots.actions" #tools>
            <slot name="tools">
                <slot name="actions" />
            </slot>
        </template>
        <template v-if="$slots.create || $slots.primary || $slots.right" #create>
            <slot name="create">
                <slot name="primary">
                    <slot name="right" />
                </slot>
            </slot>
        </template>
    </ActionBar>
</template>

<script setup>
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
})
</script>
