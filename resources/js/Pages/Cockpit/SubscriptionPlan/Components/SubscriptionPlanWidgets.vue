<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <Widget :icon="faLayerGroup" title="Total Paket">
            {{ plansCount }} <span class="text-xs font-normal text-neutral-500">Varian</span>
        </Widget>
        <Widget :icon="faCheckCircle" title="Paket Aktif">
            <span class="text-success">{{ activeCount }}</span>
            <span class="text-xs font-normal text-neutral-400 ml-1.5"
                >({{ inactiveCount }} nonaktif)</span
            >
        </Widget>
        <Widget :icon="faEye" title="Katalog Publik">
            <span class="text-sky-700">{{ publicCount }}</span>
            <span class="text-xs font-normal text-neutral-400 ml-1.5"
                >({{ hiddenCount }} hidden)</span
            >
        </Widget>
        <Widget :icon="faStore" title="Pelanggan Aktif">
            {{ totalSubscribersCount }}
            <span class="text-xs font-normal text-neutral-500">Merchant</span>
        </Widget>
    </div>
</template>

<script setup>
import Widget from '@/Components/Widgets/Widget.vue'
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
