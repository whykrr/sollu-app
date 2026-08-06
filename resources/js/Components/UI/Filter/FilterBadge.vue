<template>
    <div class="filter-badge">
        <span><slot /></span>
        <button
            type="button"
            @click="$emit('remove')"
            class="filter-badge-remove"
            title="Hapus filter"
        >
            ✕
        </button>
    </div>
</template>

<script setup>
defineEmits(['remove']);
</script>
