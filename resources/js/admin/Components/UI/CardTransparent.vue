<template>
    <div class="card !bg-transparent rounded-none">
        <div>
            <img
                v-if="image"
                :src="image"
                alt="Card Image"
                class="block-inline w-full"
            />
        </div>
        <div class="card-header !p-0">
            <h2 class="text-2xl font-semibold">{{ title }}</h2>
            <div class="flex flex-row gap-2">
                <slot name="buttons" />
            </div>
        </div>
        <div class="">
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
