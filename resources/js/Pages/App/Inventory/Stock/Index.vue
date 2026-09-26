<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Stok Saat Ini" />
        </template>

        <template #widgets>
            <StockWidgets :summary="summary" />
        </template>

        <template #filter>
            <StockFilter
                :filters="filters"
                :categories="categories"
                @open-costing-modal="openCostingModal(false)"
            />
        </template>

        <Table :headers="headers" :data="stocks.data" :action="false" @row-click="openDetail">
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
                    <span class="text-xs text-gray-500 font-normal">{{ item.uom }}</span>
                </span>
            </template>
            <template #status="{ item }">
                <span v-if="item.current_stock <= 0" class="badge badge-danger">Habis</span>
                <span v-else-if="item.is_low_stock" class="badge badge-warning">Menipis</span>
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
import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import StockWidgets from './Components/StockWidgets.vue'
import StockFilter from './Components/StockFilter.vue'
import Detail from './Components/Detail.vue'
import InventoryCostingModal from './Components/InventoryCostingModal.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const page = usePage()

const activeCostingMethod = computed(() => {
    return page.props.auth?.business?.inventory_costing_method || 'fifo'
})

const isCostingConfigured = computed(() => {
    return Boolean(page.props.auth?.business?.is_costing_configured)
})

const openCostingModal = (isSetupMode = false) => {
    modalStore.open({
        type: 'info',
        title: isSetupMode
            ? 'Yuk, Tentukan Metode Perhitungan Aset Tokomu 👋'
            : 'Pengaturan Metode Perhitungan Aset Inventaris',
        component: InventoryCostingModal,
        props: {
            currentMethod: activeCostingMethod.value,
            isSetupMode,
        },
        size: 'max-w-2xl',
        showFooter: false,
    })
}

onMounted(() => {
    // Seperti pendekatan setup peran pada modul karyawan:
    // Jika belum pernah dikonfigurasi secara eksplisit, tampilkan modal panduan setup
    if (!isCostingConfigured.value) {
        openCostingModal(true)
    }
})

defineProps({
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
})

const headers = [
    { label: 'Outlet', field: 'outlet_name', sortable: false },
    { label: 'Item', field: 'item_name', sortable: true },
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
]

const openDetail = item => {
    popUpStore.open({
        title: item.item_name,
        subTitle: item.category_name || item.outlet_name || '',
        size: 'xl',
        component: Detail,
        props: { item },
    })
}
</script>
