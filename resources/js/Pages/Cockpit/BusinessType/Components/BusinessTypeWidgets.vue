<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <WidgetMini :icon="faBriefcase" title="Total Jenis Bisnis" variant="main">
            {{ totalTypes }} <span class="text-xs font-normal text-neutral-500">Tipe</span>
        </WidgetMini>

        <WidgetMini
            :icon="faEye"
            title="Tampil di Registrasi"
            variant="success"
            value-class="text-success"
        >
            {{ visibleCount }} <span class="text-xs font-bold text-success">Aktif</span>
            <span class="text-xs font-normal text-neutral-400 ml-1.5"
                >({{ hiddenCount }} hidden)</span
            >
        </WidgetMini>

        <WidgetMini
            :icon="faSliders"
            title="Rata-Rata Fitur"
            variant="sky"
            value-class="text-sky-700"
        >
            {{ averageFeaturesCount }}
            <span class="text-xs font-normal text-neutral-500">Fitur / Tipe</span>
        </WidgetMini>

        <WidgetMini :icon="faStore" title="Total Merchant" variant="amber">
            {{ totalMerchantsCount }}
            <span class="text-xs font-normal text-neutral-500">Terdaftar</span>
        </WidgetMini>
    </div>
</template>

<script setup>
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import { faBriefcase, faEye, faSliders, faStore } from '@fortawesome/free-solid-svg-icons'

defineProps({
    totalTypes: {
        type: Number,
        default: 0,
    },
    visibleCount: {
        type: Number,
        default: 0,
    },
    hiddenCount: {
        type: Number,
        default: 0,
    },
    averageFeaturesCount: {
        type: Number,
        default: 0,
    },
    totalMerchantsCount: {
        type: Number,
        default: 0,
    },
})
</script>
