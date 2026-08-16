<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Stok Saat Ini">
                <ExportDropdown :items="exportItems" />
            </MainPageHeader>

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

            <StockFilter :filters="filters" :categories="categories" />
        </template>

        <Table
            :headers="headers"
            :data="stocks.data"
            :action="false"
            @row-click="openDetail"
        >
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
        </Table>

        <template #footer>
            <Pagination
                :links="stocks.links"
                :from="stocks.from"
                :to="stocks.to"
                :total="stocks.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Widget from '@/Components/Widgets/Widget.vue';
import ExportDropdown from '@/Components/UI/ExportDropdown.vue';
import {
    faBox,
    faMoneyBillWave,
    faExclamationTriangle,
    faTimesCircle,
    faFileExcel,
    faFilePdf,
} from '@fortawesome/free-solid-svg-icons';
import StockFilter from './Components/StockFilter.vue';
import Detail from './Components/Detail.vue';
import { usePopUpStore } from '@/store/popup';

const popUpStore = usePopUpStore();

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

const exportCsv = () => {
    router.get(
        route('inventories.stocks.export-csv', props.filters),
        {},
        { preserveScroll: true, preserveState: true },
    );
};

const exportPdf = () => {
    router.get(
        route('inventories.stocks.export-pdf-list', props.filters),
        {},
        { preserveScroll: true, preserveState: true },
    );
};

const exportItems = computed(() => [
    {
        label: 'Ekspor Excel / CSV',
        icon: faFileExcel,
        action: exportCsv,
        class: 'text-emerald-600',
    },
    {
        label: 'Ekspor PDF',
        icon: faFilePdf,
        action: exportPdf,
        class: 'text-rose-600',
    },
]);

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

const openDetail = (item) => {
    popUpStore.open({
        title: item.item_name,
        subTitle: '#' + item.sku,
        size: 'xl',
        component: Detail,
        props: { item },
    });
};
</script>
