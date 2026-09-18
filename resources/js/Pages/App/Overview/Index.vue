<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Dashboard"
                description="Ringkasan performa penjualan, tren omset, wawasan metode pembayaran, dan status inventaris"
            >
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 w-full sm:w-auto">
                    <!-- Outlet Selector -->
                    <div v-if="outletOptions.length > 0" class="w-full sm:w-48">
                        <GroupDropdownIconField
                            id="outlet-filter"
                            v-model="formFilters.outlet"
                            :icon="faStore"
                            class="sm"
                            :options="[{ value: '', label: 'Semua Outlet' }, ...outletOptions]"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Date Preset Filter -->
                    <div class="w-full sm:w-48">
                        <GroupDropdownIconField
                            id="period-filter"
                            v-model="formFilters.period"
                            :icon="faCalendarDays"
                            class="sm"
                            :options="periodOptions"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </MainPageHeader>
        </template>

        <!-- Email Verification Banner -->
        <div v-if="auth?.email_verified_at === null" class="alert alert-warning mb-2 shadow-xs">
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2"
            >
                <div>
                    <strong class="block">Verifikasi Email</strong>
                    <span class="text-xs sm:text-sm text-neutral-700">
                        Cek email Anda untuk verifikasi sebelum menggunakan fitur lengkap aplikasi.
                    </span>
                </div>
                <Link
                    as="button"
                    method="post"
                    :href="route('verification.send')"
                    class="btn btn-highlight-warning btn-sm shrink-0"
                >
                    <FontAwesomeIcon :icon="faRotateRight" />
                    Kirim Ulang Email
                </Link>
            </div>
        </div>

        <template #widgets>
            <AppDashboardKpiWidgets
                :total-sales="totalSales"
                :total-transactions="totalTransactions"
                :average-sales="averageSales"
                :low-stock-count="lowStockCount"
                :period-label="filters?.period_label || 'periode lalu'"
            />
        </template>

        <!-- Visual Analytics & Operational Grid -->
        <div class="flex flex-col gap-2 pb-4">
            <!-- Row 1: Sales Trend & Category Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
                <div class="lg:col-span-2">
                    <SalesTrendChart :trend="salesTrend" :period-label="filters?.period_label" />
                </div>
                <div class="lg:col-span-1">
                    <CategorySalesChart :category-sales="categorySalesTrend" />
                </div>
            </div>

            <!-- Row 2: Payment Method & Most Sold Products -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
                <div class="lg:col-span-2">
                    <PaymentMethodChart :payment-methods="paymentMethodSummary" />
                </div>
                <div class="lg:col-span-1">
                    <TableMostSoldProduct :data="mostSoldProducts" />
                </div>
            </div>

            <!-- Row 3: Inventory Alerts & Dead Stock -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                <FeatureLock :feature="$enums.FeatureEnum.INVENTORY_MANAGEMENT">
                    <TableProductLowStock :data="lowStockProduct" />
                </FeatureLock>
                <TableProductNotSold :data="productNotSold" />
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCalendarDays, faRotateRight, faStore } from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue'
import FeatureLock from '@/Components/UI/FeatureLock.vue'
import AppDashboardKpiWidgets from './Components/AppDashboardKpiWidgets.vue'
import SalesTrendChart from './Components/SalesTrendChart.vue'
import CategorySalesChart from './Components/CategorySalesChart.vue'
import PaymentMethodChart from './Components/PaymentMethodChart.vue'
import TableMostSoldProduct from './Components/TableMostSoldProduct.vue'
import TableProductNotSold from './Components/TableProductNotSold.vue'
import TableProductLowStock from './Components/TableProductLowStock.vue'
import { useAuth } from '@/Composable/useAuth'

const { user, outlets: userOutlets } = useAuth()
const auth = user

const outletOptions = computed(() => {
    if (!userOutlets.value || !Array.isArray(userOutlets.value)) return []
    return userOutlets.value.map(store => ({
        value: store.id,
        label: store.name,
    }))
})

const periodOptions = [
    { value: 'today', label: 'Hari Ini' },
    { value: 'yesterday', label: 'Kemarin' },
    { value: '7_days', label: '7 Hari Terakhir' },
    { value: 'last_30_days', label: '30 Hari Terakhir' },
    { value: 'this_month', label: 'Bulan Ini' },
    { value: 'last_month', label: 'Bulan Lalu' },
    { value: 'this_year', label: 'Tahun Ini' },
    { value: 'all_time', label: 'Sepanjang Waktu' },
]

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({ period: 'today', outlet: '', period_label: 'Hari Ini' }),
    },
    totalSales: {
        type: Object,
        default: () => ({ now: 0, previous: 0, growth: 0 }),
    },
    totalTransactions: {
        type: Object,
        default: () => ({ now: 0, previous: 0, growth: 0 }),
    },
    averageSales: {
        type: Object,
        default: () => ({ now: 0, previous: 0, growth: 0 }),
    },
    lowStockCount: {
        type: Number,
        default: 0,
    },
    salesTrend: {
        type: Object,
        default: () => ({ label: [], value: [] }),
    },
    categorySalesTrend: {
        type: Object,
        default: () => ({ label: [], value: [] }),
    },
    paymentMethodSummary: {
        type: Object,
        default: () => ({ label: [], value: [], revenue: [] }),
    },
    mostSoldProducts: {
        type: Array,
        default: () => [],
    },
    lowStockProduct: {
        type: Array,
        default: () => [],
    },
    productNotSold: {
        type: Array,
        default: () => [],
    },
})

const formFilters = ref({
    outlet: props.filters?.outlet || '',
    period: props.filters?.period || 'today',
})

watch(
    () => props.filters,
    newFilters => {
        if (newFilters) {
            formFilters.value.outlet = newFilters.outlet || ''
            formFilters.value.period = newFilters.period || 'today'
        }
    },
    { deep: true }
)

const applyFilters = () => {
    const params = {}
    if (formFilters.value.period) {
        params.period = formFilters.value.period
    }
    if (formFilters.value.outlet) {
        params.outlet = formFilters.value.outlet
    }

    router.get(route('overview'), params, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
