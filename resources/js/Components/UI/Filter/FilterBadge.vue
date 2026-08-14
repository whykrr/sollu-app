<template>
    <div class="filter-badge">
        <span><slot /></span>
        <button
            type="button"
            class="filter-badge-remove"
            title="Hapus filter"
            @click="$emit('remove')"
        >
            ✕
        </button>
    </div>
</template>

<script setup>
defineEmits(['remove']);
</script>
