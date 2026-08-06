<template>
    <Container>
        <template #header>
            <ContainerHeader title="Stok Saat Ini" />

            <!-- Summary Card -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <Widget title="Total Produk" :icon="faBox" class="widget-main">
                    {{ summary.total_item }} Barang
                </Widget>
                <Widget
                    title="Total Nilai Stok"
                    :icon="faMoneyBillWave"
                    class="widget-teal"
                >
                    Rp
                    {{ summary.total_nilai_stok?.toLocaleString('id-ID') || 0 }}
                </Widget>
                <Widget
                    title="Stok Menipis"
                    :icon="faExclamationTriangle"
                    class="widget-warning"
                >
                    {{ summary.stok_menipis }} Item
                </Widget>
                <Widget
                    title="Stok Habis"
                    :icon="faTimesCircle"
                    class="widget-danger"
                >
                    {{ summary.stok_habis }} Item
                </Widget>
            </div>

            <StockFilter
                :filters="filters"
                :categories="categories"
            />
        </template>

        <Table :headers="headers" :data="stocks.data" :action="true">
            <template #category_name="{ item }">
                {{ item.category_name || '-' }}
            </template>
            <template #minimum_stock="{ item }">
                {{ item.minimum_stock_formatted }}
                <span class="text-xs text-gray-500">{{ item.uom }}</span>
            </template>
            <template #current_stock="{ item }">
                <span
                    class="font-semibold"
                    :class="
                        item.current_stock <= 0
                            ? 'text-danger'
                            : item.is_low_stock
                              ? 'text-warning'
                              : ''
                    "
                >
                    {{ item.current_stock_formatted }}
                    <span class="text-xs text-gray-500 font-normal">{{
                        item.uom
                    }}</span>
                </span>
            </template>
            <template #status="{ item }">
                <span v-if="item.current_stock <= 0" class="badge badge-danger"
                    >Habis</span
                >
                <span v-else-if="item.is_low_stock" class="badge badge-warning"
                    >Menipis</span
                >
                <span v-else class="badge badge-success">Aman</span>
            </template>
            <template #actions="{ item }">
                <button class="btn btn-flat btn-sm" @click="openDetail(item)">
                    <FontAwesomeIcon :icon="faEye" title="Detail" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="stocks.links"
                :from="stocks.from"
                :to="stocks.to"
                :total="stocks.total"
            />
        </template>

        <Detail :show="showDetail" :item="selectedItem" @close="closeDetail" />
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Widget from '@/Components/Widgets/Widget.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faEye,
    faBox,
    faMoneyBillWave,
    faExclamationTriangle,
    faTimesCircle,
    faStore,
} from '@fortawesome/free-solid-svg-icons';
import StockFilter from './Components/StockFilter.vue';
import Detail from './Components/Detail.vue';
import { formatDateTimeSimple } from '@/Composable/date';

const props = defineProps({
    stocks: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const headers = [
    { label: 'Outlet', field: 'outlet_name', sortable: false },
    { label: 'Item', field: 'item_name', sortable: true },
    { label: 'SKU', field: 'sku', sortable: true },
    {
        label: 'Kategori',
        field: 'category_name',
        slot: 'category_name',
        sortable: false,
    },
    {
        label: 'Min. Stok',
        field: 'minimum_stock',
        slot: 'minimum_stock',
        sortable: false,
    },
    {
        label: 'Stok',
        field: 'current_stock',
        slot: 'current_stock',
        sortable: true,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];

const showDetail = ref(false);
const selectedItem = ref(null);

const openDetail = (item) => {
    selectedItem.value = item;
    showDetail.value = true;
};

const closeDetail = () => {
    showDetail.value = false;
    selectedItem.value = null;
};
</script>
