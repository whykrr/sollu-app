<template>
    <div class="widget">
        <div class="flex flex-row items-center gap-3">
            <div class="widget-icon bg-main/10 text-main shrink-0">
                <FontAwesomeIcon :icon class="text-lg" />
            </div>
            <h2 class="font-medium text-sm text-neutral-600">{{ title }}</h2>
        </div>
        <div class="text-xl text-neutral-800 font-bold mt-2">
            <slot />
        </div>
        <div v-if="traction" class="mt-1">
            <div class="inline-flex gap-1.5 items-center">
                <FontAwesomeIcon
                    v-if="traction === 'up'"
                    :icon="faArrowUp"
                    class="text-xs text-success"
                />
                <FontAwesomeIcon
                    v-else
                    :icon="faArrowDown"
                    class="text-xs text-danger"
                />
                <span
                    class="text-xs font-medium"
                    :class="{
                        'text-success': traction === 'up',
                        'text-danger': traction === 'down',
                    }"
                    >{{ tractionPercentage }}%</span
                >
                <span class="text-xs text-neutral-500">{{ descriptors }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    faArrowDown,
    faArrowUp,
    faBell,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { computed, ref } from 'vue';

const props = defineProps({
    icon: Object,
    title: String,
    traction: {
        type: String,
    },
    tractionPercentage: {
        type: Number,
        default: 0,
    },
    descriptors: {
        type: String,
        default: 'dari bulan lalu',
    },
});
</script>
