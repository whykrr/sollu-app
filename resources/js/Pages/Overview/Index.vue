<template>
    <Container>
        <div
            v-if="auth.email_verified_at === null"
            class="alert alert-warning mb-2"
        >
            <strong>Verifikasi Email</strong>
            <div class="flex justify-between items-center">
                <span class="text-sm"
                    >Cek email Anda untuk verifikasi sebelum menggunakan
                    fitur.</span
                >
                <Link
                    as="button"
                    method="post"
                    :href="route('verification.send')"
                    class="btn btn-highlight-success btn-sm"
                >
                    <FontAwesomeIcon :icon="faRotateRight" class="left-0" />
                    Kirim Ulang
                </Link>
            </div>
        </div>

        <TransactionSection
            :total-sales="totalSales"
            :total-transactions="totalTransactions"
            :average-sales="averageSales"
        />

        <div class="mb-2">
            <SalesTrendChart :trend="salesTrend" />
        </div>

        <div class="grid grid-cols-3 gap-2">
            <div class="col-span-2">
                <TableMostSoldProduct :data="mostSoldProducts" />
            </div>
            <div class="">
                <div class="flex flex-col gap-2">
                    <TableProductLowStock :data="lowStockProduct" />
                    <TableProductNotSold :data="productNotSold" />
                </div>
            </div>
        </div>
    </Container>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCalendarDays,
    faCalendarTimes,
    faMapMarkerAlt,
    faRotateRight,
    faUserShield,
} from '@fortawesome/free-solid-svg-icons';
import TransactionSection from './Components/TransactionSection.vue';
import Container from '@/Components/UI/Container.vue';
import SalesTrendChart from './Components/SalesTrendChart.vue';
import TableMostSoldProduct from './Components/TableMostSoldProduct.vue';
import TableProductNotSold from './Components/TableProductNotSold.vue';
import TableProductLowStock from './Components/TableProductLowStock.vue';
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';

const auth = usePage().props.auth;
const outlets = auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}));

const props = defineProps({
    filters: Object,
    totalSales: Object,
    totalTransactions: Object,
    averageSales: Object,
    salesTrend: {
        label: Array,
        value: Array,
    },
    mostSoldProducts: Array,
    lowStockProduct: Array,
    productNotSold: Array,
});

const formFilters = useForm({
    outlet: props.filters?.outlet ?? '',
    type: props.filters?.type ?? 'this_month',
});
</script>
