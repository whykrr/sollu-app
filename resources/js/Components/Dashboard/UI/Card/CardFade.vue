<template>
    <div class="card rounded-lg drop-shadow relative">
        <div class="p-0">
            <div class="relative rounded-lg overflow-hidden">
                <img
                    v-if="image"
                    :src="image"
                    alt="Card Image"
                    class="block-inline w-full"
                />
                <span
                    class="absolute p-2 inset-0 backdrop-blur-lg bg-white/50 [mask-image:linear-gradient(to_top,black_15%,transparent_90%)]"
                />
                <div
                    class="absolute inset-0 p-2 flex flex-col justify-end h-full w-full gap-1"
                >
                    <div>
                        <h2 class="text-base font-semibold">{{ title }}</h2>
                        <div class="text-sm">
                            <slot />
                        </div>
                    </div>
                    <div
                        class="flex flex-row gap-2 justify-between items-center"
                    >
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    image: String,
    title: String,
});
</script>
