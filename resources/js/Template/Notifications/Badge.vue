<template>
    <div class="grid grid-flow-col gap-4 w-full">
        <div class="flex flex-col gap-4">
            <Card title="Badges">
                <div class="flex flex-col gap-4">
                    <div class="text-2xl">
                        Example 2xl
                        <span class="badge badge-gray-600">New</span>
                    </div>
                    <div class="text-xl">
                        Example xl
                        <span class="badge badge-gray-600">New</span>
                    </div>
                    <div class="text-lg">
                        Example lg
                        <span class="badge badge-gray-600">New</span>
                    </div>
                    <div class="text">
                        Example md
                        <span class="badge badge-gray-600">New</span>
                    </div>
                    <div class="text-sm">
                        Example sm
                        <span class="badge badge-gray-600">New</span>
                    </div>
                    <div class="text-xs">
                        Example xs
                        <span class="badge badge-gray-600">New</span>
                    </div>
                </div>
            </Card>
            <Card title="Badges Variation">
                <div class="grid gap-2">
                    <span class="badge badge-main">Main</span>
                    <span class="badge badge-secondary">Secondary</span>
                    <span class="badge badge-info">info</span>
                    <span class="badge badge-success">Success</span>
                    <span class="badge badge-warning">Warning</span>
                    <span class="badge badge-danger">Danger</span>
                    <p>Available for all color and shades</p>
                </div>
            </Card>
        </div>
        <div class="flex flex-col gap-4">
            <Card title="Badges Pill">
                <div class="grid grid-flow-row gap-2">
                    <span class="badge pill badge-main">Main</span>
                    <span class="badge pill badge-secondary">Secondary</span>
                    <span class="badge pill badge-info">info</span>
                    <span class="badge pill badge-success">Success</span>
                    <span class="badge pill badge-warning">Warning</span>
                    <span class="badge pill badge-danger">Danger</span>
                    <p>Available for all color and shades</p>
                </div>
            </Card>
            <Card title="Badges Doted">
                <div class="grid grid-flow-row gap-2">
                    <span class="badge-doted badge-main">Main</span>
                    <span class="badge-doted badge-secondary">Secondary</span>
                    <span class="badge-doted badge-info">info</span>
                    <span class="badge-doted badge-success">Success</span>
                    <span class="badge-doted badge-warning">Warning</span>
                    <span class="badge-doted badge-danger">Danger</span>
                    <p>Available for all color and shades</p>
                </div>
            </Card>
        </div>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
