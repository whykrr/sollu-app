<template>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
        <WidgetMini :icon="faStore" title="Total Merchant" variant="main">
            {{ metrics.total_merchants || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Bisnis</span>
        </WidgetMini>
        <WidgetMini
            :icon="faCheckCircle"
            title="Merchant Aktif"
            variant="success"
            value-class="text-success"
        >
            {{ metrics.active_merchants || 0 }}
            <span class="text-xs font-normal text-neutral-400 ml-1.5"
                >({{ metrics.suspended_merchants || 0 }} nonaktif)</span
            >
        </WidgetMini>
        <WidgetMini
            :icon="faLayerGroup"
            title="Langganan Aktif"
            variant="purple"
            value-class="text-purple-600"
        >
            {{ metrics.active_subscribers || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Berbayar</span>
        </WidgetMini>
        <WidgetMini :icon="faClock" title="Masa Trial" variant="amber" value-class="text-amber-600">
            {{ metrics.trial_merchants || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Merchant</span>
        </WidgetMini>
        <WidgetMini
            :icon="faBuilding"
            title="Total Outlet"
            variant="sky"
            value-class="text-sky-700"
        >
            {{ metrics.total_outlets || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Cabang</span>
        </WidgetMini>
    </div>
</template>

<script setup>
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
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
