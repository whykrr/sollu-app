<template>
    <div class="flex flex-col gap-4 h-full relative">
        <div
            class="flex flex-col sm:flex-row gap-4 justify-between w-full relative z-10"
        >
            <div class="w-full sm:w-1/2">
                <h1 class="text-2xl font-bold text-neutral-800">
                    Cockpit Dashboard
                </h1>
                <div class="text-sm text-neutral-500">
                    Platform Control Center Overview
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <div
                class="bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60"
            >
                <div class="text-neutral-500 text-sm font-medium">
                    Active Merchants
                </div>
                <div class="text-2xl font-bold mt-1 text-neutral-800">
                    1,245
                </div>
                <div class="text-xs text-success mt-2 flex items-center gap-1">
                    <FontAwesomeIcon :icon="faArrowUp" /> 12% from last month
                </div>
            </div>
            <div
                class="bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60"
            >
                <div class="text-neutral-500 text-sm font-medium">
                    Active Outlets
                </div>
                <div class="text-2xl font-bold mt-1 text-neutral-800">
                    3,892
                </div>
                <div class="text-xs text-success mt-2 flex items-center gap-1">
                    <FontAwesomeIcon :icon="faArrowUp" /> 8% from last month
                </div>
            </div>
            <div
                class="bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60"
            >
                <div class="text-neutral-500 text-sm font-medium">MRR</div>
                <div class="text-2xl font-bold mt-1 text-neutral-800">
                    Rp 145.2M
                </div>
                <div class="text-xs text-success mt-2 flex items-center gap-1">
                    <FontAwesomeIcon :icon="faArrowUp" /> 15% from last month
                </div>
            </div>
            <div
                class="bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60"
            >
                <div class="text-neutral-500 text-sm font-medium">
                    Pending Validation
                </div>
                <div class="text-2xl font-bold mt-1 text-warning">24</div>
                <div class="text-xs text-neutral-400 mt-2">
                    Needs manual review
                </div>
            </div>
        </div>

        <!-- More Dashboard widgets can be added below -->
        <div
            class="bg-white flex-1 mt-2 rounded-xl shadow-sm border border-neutral-200/60 p-4"
        >
            <div class="text-neutral-600 font-medium mb-4">Revenue Growth</div>
            <div
                class="flex items-center justify-center h-48 bg-neutral-50 rounded-lg border border-dashed border-neutral-200"
            >
                <span class="text-neutral-400">Chart Placeholder</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { faArrowUp, faArrowDown } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
</script>
