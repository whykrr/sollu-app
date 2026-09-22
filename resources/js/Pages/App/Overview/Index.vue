<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Dashboard"
                description="Ringkasan performa penjualan, tren omset, wawasan metode pembayaran, dan status inventaris"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Outlet Dropdown -->
                    <FilterDropdown
                        v-if="outletOptions.length > 1 && !selectedOutlet"
                        v-model="formFilters.outlet"
                        label="Outlet"
                        :options="outletOptions"
                        :icon="faStore"
                        all-option-label="Semua Outlet"
                        :searchable="true"
                        @change="applyFilters"
                    />

                    <!-- Date Preset Dropdown -->
                    <FilterPresetDate
                        v-model="formFilters.period"
                        v-model:start-date="formFilters.start_date"
                        v-model:end-date="formFilters.end_date"
                        @change="applyFilters"
                    />
                </div>
            </MainPageHeader>
        </template>

        <!-- Email Verification Banner -->
        <div v-if="auth?.email_verified_at === null" class="alert alert-warning mb-2">
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
import { faRotateRight, faStore } from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FeatureLock from '@/Components/UI/FeatureLock.vue'
import AppDashboardKpiWidgets from './Components/AppDashboardKpiWidgets.vue'
import SalesTrendChart from './Components/SalesTrendChart.vue'
import CategorySalesChart from './Components/CategorySalesChart.vue'
import PaymentMethodChart from './Components/PaymentMethodChart.vue'
import TableMostSoldProduct from './Components/TableMostSoldProduct.vue'
import TableProductNotSold from './Components/TableProductNotSold.vue'
import TableProductLowStock from './Components/TableProductLowStock.vue'
import { useAuth } from '@/Composable/useAuth'

const { user, outlets: userOutlets, selectedOutlet } = useAuth()
const auth = user

const outletOptions = computed(() => {
    if (!userOutlets.value || !Array.isArray(userOutlets.value)) return []
    return userOutlets.value.map(store => ({
        value: store.id,
        label: store.name,
    }))
})

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
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
})

watch(
    () => props.filters,
    newFilters => {
        if (newFilters) {
            formFilters.value.outlet = newFilters.outlet || ''
            formFilters.value.period = newFilters.period || 'today'
            formFilters.value.start_date = newFilters.start_date || ''
            formFilters.value.end_date = newFilters.end_date || ''
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
    if (formFilters.value.period === 'custom') {
        if (formFilters.value.start_date) params.start_date = formFilters.value.start_date
        if (formFilters.value.end_date) params.end_date = formFilters.value.end_date
    }

    router.get(route('overview'), params, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
