<template>
    <div class="nav-section">
        <div v-if="separator" class="py-0.5">
            <div class="w-full border-t pt-1 text-xs">
                {{ label }}
            </div>
        </div>
        <div v-else class="py-0.5">
            {{ label }}
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        default: '',
    },
    separator: {
        type: Boolean,
        default: false,
    },
});
</script>
