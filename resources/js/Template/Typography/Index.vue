<template>
    <div class="flex flex-col gap-4">
        <Card title="Font Size">
            <div class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600">
                <div class="col-span-2">Size</div>
                <div class="col-span-8">Example</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-xs"
            >
                <div class="col-span-2">xs</div>
                <div class="col-span-8">Extra Small</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-sm"
            >
                <div class="col-span-2">sm</div>
                <div class="col-span-8">Small</div>
            </div>
            <div class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600">
                <div class="col-span-2">normal</div>
                <div class="col-span-8">Normal</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-lg"
            >
                <div class="col-span-2">lg</div>
                <div class="col-span-8">Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-xl"
            >
                <div class="col-span-2">xl</div>
                <div class="col-span-8">Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-2xl"
            >
                <div class="col-span-2">2xl</div>
                <div class="col-span-8">2x Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-3xl"
            >
                <div class="col-span-2">3xl</div>
                <div class="col-span-8">3x Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-4xl"
            >
                <div class="col-span-2">4xl</div>
                <div class="col-span-8">4x Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-5xl"
            >
                <div class="col-span-2">5xl</div>
                <div class="col-span-8">5x Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-6xl"
            >
                <div class="col-span-2">6xl</div>
                <div class="col-span-8">6x Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-7xl"
            >
                <div class="col-span-2">7xl</div>
                <div class="col-span-8">7x Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-8xl"
            >
                <div class="col-span-2">8xl</div>
                <div class="col-span-8">8x Extra Large</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 text-9xl"
            >
                <div class="col-span-2">9xl</div>
                <div class="col-span-8">9x Extra Large</div>
            </div>
        </Card>
        <Card title="Font Weight">
            <div class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600">
                <div class="col-span-2">Class</div>
                <div class="col-span-8">Example</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 font-thin"
            >
                <div class="col-span-2">.font-thin</div>
                <div class="col-span-8">Lorem ipsum dolor sit amet</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 font-normal"
            >
                <div class="col-span-2">.font-normal</div>
                <div class="col-span-8">Lorem ipsum dolor sit amet</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 font-medium"
            >
                <div class="col-span-2">.font-medium</div>
                <div class="col-span-8">Lorem ipsum dolor sit amet</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 font-semibold"
            >
                <div class="col-span-2">.font-semibold</div>
                <div class="col-span-8">Lorem ipsum dolor sit amet</div>
            </div>
            <div
                class="grid grid-cols-10 gap-4 border-b-2 border-indigo-600 font-bold"
            >
                <div class="col-span-2">.font-bold</div>
                <div class="col-span-8">Lorem ipsum dolor sit amet</div>
            </div>
        </Card>
    </div>
</template>

<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
