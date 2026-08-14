<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Dashboard Ringkasan">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 w-full sm:w-auto">
                    <!-- Outlet Selector -->
                    <div v-if="outletOptions.length > 0" class="w-full sm:w-48">
                        <GroupDropdownIconField
                            id="outlet-filter"
                            v-model="formFilters.outlet"
                            :icon="faStore"
                            class="sm"
                            :options="[
                                { value: '', label: 'Semua Outlet' },
                                ...outletOptions
                            ]"
                            @change="applyFilters"
                        />
                    </div>

                    <!-- Date Preset Filter -->
                    <div class="w-full sm:w-44">
                        <GroupDropdownIconField
                            id="period-filter"
                            v-model="formFilters.period"
                            :icon="faCalendarDays"
                            class="sm"
                            :options="[
                                { value: 'today', label: 'Hari Ini' },
                                { value: 'yesterday', label: 'Kemarin' },
                                { value: '7_days', label: '7 Hari Terakhir' },
                                { value: 'this_month', label: 'Bulan Ini' }
                            ]"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </MainPageHeader>
        </template>

        <!-- Email Verification Banner -->
        <div
            v-if="auth?.email_verified_at === null"
            class="alert alert-warning mb-3 shadow-xs"
        >
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
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

        <!-- 4 KPI Cards Section -->
        <TransactionSection
            :total-sales="totalSales"
            :total-transactions="totalTransactions"
            :average-sales="averageSales"
            :period-label="filters?.period_label || 'periode ini'"
        />

        <!-- Charts Grid Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 mb-2">
            <div class="lg:col-span-2">
                <SalesTrendChart :trend="salesTrend" />
            </div>
            <div class="lg:col-span-1">
                <CategorySalesChart :category-sales="categorySalesTrend" />
            </div>
        </div>

        <!-- Payment Method & Operations Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
            <div class="lg:col-span-2 flex flex-col gap-2">
                <PaymentMethodChart :payment-methods="paymentMethodSummary" />
                <TableMostSoldProduct :data="mostSoldProducts" />
            </div>
            <div class="lg:col-span-1 flex flex-col gap-2">
                <TableProductLowStock :data="lowStockProduct" />
                <TableProductNotSold :data="productNotSold" />
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCalendarDays,
    faRotateRight,
    faStore,
} from '@fortawesome/free-solid-svg-icons';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import TransactionSection from './Components/TransactionSection.vue';
import SalesTrendChart from './Components/SalesTrendChart.vue';
import CategorySalesChart from './Components/CategorySalesChart.vue';
import PaymentMethodChart from './Components/PaymentMethodChart.vue';
import TableMostSoldProduct from './Components/TableMostSoldProduct.vue';
import TableProductNotSold from './Components/TableProductNotSold.vue';
import TableProductLowStock from './Components/TableProductLowStock.vue';
import { useAuth } from '@/Composable/useAuth';

const { user, outlets: userOutlets } = useAuth();
const auth = user;

const outletOptions = computed(() => {
    if (!userOutlets.value || !Array.isArray(userOutlets.value)) return [];
    return userOutlets.value.map((store) => ({
        value: store.id,
        label: store.name,
    }));
});

const props = defineProps({
    filters: Object,
    totalSales: Object,
    totalTransactions: Object,
    averageSales: Object,
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
    mostSoldProducts: Array,
    lowStockProduct: Array,
    productNotSold: Array,
});

const formFilters = useForm({
    outlet: props.filters?.outlet ?? '',
    period: props.filters?.period ?? 'today',
});

const applyFilters = () => {
    formFilters.get(route('overview'), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
