<template>
    <div class="card">
        <div>
            <img
                v-if="image"
                :src="image"
                alt="Card Image"
                class="block-inline w-full"
            />
        </div>
        <div class="card-header">
            <h2 class="text-lg font-semibold">{{ title }}</h2>
            <div class="flex flex-row gap-2">
                <slot name="buttons" />
            </div>
        </div>
        <div class="px-4 pb-4">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    image: String,
    title: String,
});
</script>
