<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Dashboard"
                description="Ringkasan ekosistem SaaS, tren pertumbuhan pendapatan, akuisisi pengguna, dan antrean operasional"
            >
                <div class="w-full sm:w-52">
                    <GroupDropdownIconField
                        id="period-filter"
                        v-model="periodFilter"
                        :icon="faCalendarDays"
                        class="sm"
                        :options="periodOptions"
                        @change="handlePeriodChange"
                    />
                </div>
            </MainPageHeader>
        </template>

        <template #widgets>
            <CockpitKpiWidgets :metrics="metrics" />
        </template>

        <!-- Visual Analytics Grid -->
        <div class="flex flex-col gap-2 pb-4">
            <!-- Row 1: Revenue Trend & Plan Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
                <div class="lg:col-span-2">
                    <CockpitRevenueTrendChart
                        :revenue-trend="revenueTrend"
                        :period-label="filters?.period_label"
                    />
                </div>
                <div class="lg:col-span-1">
                    <CockpitPlanDistributionChart :plan-distribution="planDistribution" />
                </div>
            </div>

            <!-- Row 2: Merchant Acquisition & Business Type Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
                <div class="lg:col-span-2">
                    <CockpitAcquisitionChart :acquisition-trend="acquisitionTrend" />
                </div>
                <div class="lg:col-span-1">
                    <CockpitBusinessTypeChart
                        :business-type-distribution="businessTypeDistribution"
                    />
                </div>
            </div>

            <!-- Row 3: Actionable Queues & Recent Registrations -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                <CockpitPendingInvoicesTable :pending-invoices="pendingInvoices" />
                <CockpitRecentMerchantsTable :recent-merchants="recentMerchants" />
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue'
import CockpitKpiWidgets from './Components/CockpitKpiWidgets.vue'
import CockpitRevenueTrendChart from './Components/CockpitRevenueTrendChart.vue'
import CockpitAcquisitionChart from './Components/CockpitAcquisitionChart.vue'
import CockpitPlanDistributionChart from './Components/CockpitPlanDistributionChart.vue'
import CockpitBusinessTypeChart from './Components/CockpitBusinessTypeChart.vue'
import CockpitPendingInvoicesTable from './Components/CockpitPendingInvoicesTable.vue'
import CockpitRecentMerchantsTable from './Components/CockpitRecentMerchantsTable.vue'

import { faCalendarDays } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({}),
    },
    revenueTrend: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    acquisitionTrend: {
        type: Object,
        default: () => ({ labels: [], merchants: [], outlets: [] }),
    },
    planDistribution: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    businessTypeDistribution: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    pendingInvoices: {
        type: Array,
        default: () => [],
    },
    recentMerchants: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ period: 'this_month', period_label: 'Bulan Ini' }),
    },
})

const periodFilter = ref(props.filters?.period || 'this_month')

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

watch(
    () => props.filters?.period,
    newPeriod => {
        if (newPeriod) {
            periodFilter.value = newPeriod
        }
    }
)

const handlePeriodChange = val => {
    const selected = typeof val === 'string' ? val : periodFilter.value
    periodFilter.value = selected
    router.get(
        route('cockpit.dashboard'),
        { period: selected },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}
</script>
