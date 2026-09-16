<template>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
        <Widget :icon="faStore" title="Total Merchant">
            <span class="font-bold text-neutral-800">{{ metrics.total_merchants || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Bisnis</span>
        </Widget>
        <Widget :icon="faCheckCircle" title="Merchant Aktif">
            <span class="font-bold text-success">{{ metrics.active_merchants || 0 }}</span>
            <span class="text-xs font-normal text-neutral-400 ml-1.5"
                >({{ metrics.suspended_merchants || 0 }} nonaktif)</span
            >
        </Widget>
        <Widget :icon="faLayerGroup" title="Langganan Aktif">
            <span class="font-bold text-main">{{ metrics.active_subscribers || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Berbayar</span>
        </Widget>
        <Widget :icon="faClock" title="Masa Trial">
            <span class="font-bold text-amber-600">{{ metrics.trial_merchants || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Merchant</span>
        </Widget>
        <Widget :icon="faBuilding" title="Total Outlet">
            <span class="font-bold text-sky-700">{{ metrics.total_outlets || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Cabang</span>
        </Widget>
    </div>
</template>

<script setup>
import Widget from '@/Components/Widgets/Widget.vue'
import {
    faStore,
    faCheckCircle,
    faLayerGroup,
    faClock,
    faBuilding,
} from '@fortawesome/free-solid-svg-icons'

defineProps({
    metrics: {
        type: Object,
        default: () => ({
            total_merchants: 0,
            active_merchants: 0,
            suspended_merchants: 0,
            active_subscribers: 0,
            trial_merchants: 0,
            total_outlets: 0,
        }),
    },
})
</script>
