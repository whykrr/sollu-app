<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Laporan Promo & Diskon"
                description="Statistik efektivitas penggunaan kupon promo, frekuensi pemakaian, dan total diskon yang diberikan"
            />
        </template>

        <template #widgets>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <WidgetMini
                    :icon="faTags"
                    variant="main"
                    title="Total Pemakaian Promo"
                    :value="`${formatNumberID(summary?.total_usage || 0)} Kali`"
                />
                <WidgetMini
                    :icon="faPercent"
                    variant="danger"
                    title="Total Diskon Diberikan"
                    :value="formatIDR(summary?.total_discount_given || 0)"
                />
                <WidgetMini
                    :icon="faBullhorn"
                    variant="success"
                    title="Promo Aktif Dipakai"
                    :value="`${formatNumberID(summary?.total_active_promos || 0)} Promo`"
                />
            </div>
        </template>

        <template #filter>
            <PromotionFilter :filters="filters" />
        </template>

        <Table :headers="headers" :data="promotions.data" :action="false">
            <template #promo_name="{ row }">
                <span class="font-medium text-xs text-neutral-900">{{ row.promo_name }}</span>
            </template>
            <template #promo_type="{ row }">
                <span class="badge badge-neutral text-[11px] uppercase tracking-wider">
                    {{ row.promo_type || 'Diskon' }}
                </span>
            </template>
            <template #total_usage="{ row }">
                <span class="text-xs font-semibold text-neutral-800">{{ formatNumberID(row.total_usage) }} Kali</span>
            </template>
            <template #total_discount_given="{ row }">
                <span class="text-xs font-bold text-rose-600">{{ formatIDR(row.total_discount_given) }}</span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="promotions.links"
                :from="promotions.from"
                :to="promotions.to"
                :total="promotions.total"
                :per-page="promotions.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import {
    faBullhorn,
    faPercent,
    faTags,
} from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import PromotionFilter from './Components/PromotionFilter.vue'
import { formatIDR } from '@/Composable/currency-format'
import { formatNumberID } from '@/Composable/useNumberFormat'

defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    promotions: {
        type: Object,
        default: () => ({ data: [] }),
    },
})

const headers = [
    { label: 'Nama Promo', field: 'promo_name', slot: 'promo_name' },
    { label: 'Tipe', field: 'promo_type', slot: 'promo_type' },
    { label: 'Pemakaian', field: 'total_usage', slot: 'total_usage' },
    { label: 'Total Diskon Diberikan', field: 'total_discount_given', slot: 'total_discount_given' },
]
</script>

