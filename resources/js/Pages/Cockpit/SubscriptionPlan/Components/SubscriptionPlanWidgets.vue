<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <WidgetMini :icon="faLayerGroup" title="Total Paket" variant="main">
            {{ plansCount }} <span class="text-xs font-normal text-neutral-500">Varian</span>
        </WidgetMini>
        <WidgetMini
            :icon="faCheckCircle"
            title="Paket Aktif"
            variant="success"
            value-class="text-success"
        >
            {{ activeCount }}
            <span class="text-xs font-normal text-neutral-400 ml-1.5"
                >({{ inactiveCount }} nonaktif)</span
            >
        </WidgetMini>
        <WidgetMini :icon="faEye" title="Katalog Publik" variant="sky" value-class="text-sky-700">
            {{ publicCount }}
            <span class="text-xs font-normal text-neutral-400 ml-1.5"
                >({{ hiddenCount }} hidden)</span
            >
        </WidgetMini>
        <WidgetMini :icon="faStore" title="Pelanggan Aktif" variant="amber">
            {{ totalSubscribersCount }}
            <span class="text-xs font-normal text-neutral-500">Merchant</span>
        </WidgetMini>
    </div>
</template>

<script setup>
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import { faLayerGroup, faCheckCircle, faEye, faStore } from '@fortawesome/free-solid-svg-icons'

defineProps({
    plansCount: {
        type: Number,
        default: 0,
    },
    activeCount: {
        type: Number,
        default: 0,
    },
    inactiveCount: {
        type: Number,
        default: 0,
    },
    publicCount: {
        type: Number,
        default: 0,
    },
    hiddenCount: {
        type: Number,
        default: 0,
    },
    totalSubscribersCount: {
        type: Number,
        default: 0,
    },
})
</script>
