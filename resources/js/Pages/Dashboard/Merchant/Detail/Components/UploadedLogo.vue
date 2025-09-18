<template>
    <div
        class="flex justify-center items-center w-full aspect-square rounded-full border border-neutral-200 overflow-hidden"
    >
        <img :src="url" alt="Logo" class="w-full aspect-square" />
    </div>
</template>

<script setup>
defineProps({
    url: String,
});
</script>
