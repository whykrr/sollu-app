<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        <WidgetMini :icon="faBox" title="Total Produk" variant="main">
            {{ summary.total_item || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Barang</span>
        </WidgetMini>

        <WidgetMini :icon="faMoneyBillWave" title="Total Nilai Stok" variant="success">
            Rp {{ (summary.total_nilai_stok || 0).toLocaleString('id-ID') }}
        </WidgetMini>

        <WidgetMini
            :icon="faExclamationTriangle"
            title="Stok Menipis"
            variant="amber"
            value-class="text-amber-600"
        >
            {{ summary.stok_menipis || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Item</span>
        </WidgetMini>

        <WidgetMini
            :icon="faTimesCircle"
            title="Stok Habis"
            variant="danger"
            value-class="text-danger"
        >
            {{ summary.stok_habis || 0 }}
            <span class="text-xs font-normal text-neutral-500 ml-1">Item</span>
        </WidgetMini>
    </div>
</template>

<script setup>
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import {
    faBox,
    faMoneyBillWave,
    faExclamationTriangle,
    faTimesCircle,
} from '@fortawesome/free-solid-svg-icons'

defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_item: 0,
            total_nilai_stok: 0,
            stok_menipis: 0,
            stok_habis: 0,
        }),
    },
})
</script>
