<template>
    <ActionBar>
        <template #filters>
            <!-- Outlet Dropdown (Async / List) -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.outlet_id"
                label="Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Outlet"
                @change="updateQuery"
            />

            <!-- Item Type Filter -->
            <FilterDropdown
                v-model="filterForm.item_type"
                label="Tipe Item"
                :options="itemTypeOptions"
                :icon="faLayerGroup"
                all-option-label="Semua Tipe"
                @change="updateQuery"
            />

            <!-- Category Filter -->
            <FilterDropdown
                v-if="categoryOptions.length > 0"
                v-model="filterForm.category_id"
                label="Kategori"
                :options="categoryOptions"
                :icon="faBox"
                all-option-label="Semua Kategori"
                @change="updateQuery"
            />

            <!-- Stock Status Filter -->
            <FilterDropdown
                v-model="filterForm.stock_status"
                label="Status Stok"
                :options="stockStatusOptions"
                all-option-label="Semua Status"
                @change="updateQuery"
            />

            <!-- Quick Segmented Filter (Semua / Stok > 0) -->
            <FilterSegmented
                v-model="filterForm.in_stock_only"
                :options="inStockOptions"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama item, SKU, barcode..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <ActionsDropdown label="Opsi" :items="actionItems" />
        </template>
    </ActionBar>

    <!-- Import Modal -->
    <ImportCsvModal
        :show="showImportModal"
        module-name="Stok Inventori"
        :template-url="route('inventories.stocks.import-template')"
        :import-url="route('inventories.stocks.import')"
        @close="showImportModal = false"
    />
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

import {
    faStore,
    faBox,
    faLayerGroup,
    faUpload,
    faFilePdf,
    faFileExcel,
    faSliders,
} from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'
import ImportCsvModal from '@/Components/Modals/ImportCsvModal.vue'

import { useAuth } from '@/Composable/useAuth'

const emit = defineEmits(['open-costing-modal'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

const showImportModal = ref(false)

const { outlets: userOutlets, selectedOutlet } = useAuth()
const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const categoryOptions = computed(() => {
    return props.categories.map(c => ({
        value: String(c.id ?? c.value),
        label: c.name ?? c.label,
    }))
})

const itemTypeOptions = [
    { value: 'variant_sku', label: 'Produk' },
    { value: 'raw_material', label: 'Bahan Baku' },
]

const stockStatusOptions = [
    { value: 'aman', label: 'Aman' },
    { value: 'menipis', label: 'Menipis' },
    { value: 'habis', label: 'Habis' },
]

const inStockOptions = [
    { value: '', label: 'Semua Item' },
    { value: '1', label: 'Stok > 0' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet_id: props.filters?.outlet_id ? String(props.filters.outlet_id) : '',
    item_type: props.filters?.item_type ?? '',
    category_id: props.filters?.category_id ? String(props.filters.category_id) : '',
    stock_status: props.filters?.stock_status ?? '',
    is_active_only: props.filters?.is_active_only ? String(props.filters.is_active_only) : '',
    in_stock_only: props.filters?.in_stock_only ? String(props.filters.in_stock_only) : '',
})

// Watch search with debounce
watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        outlet_id: filterForm.outlet_id !== '' ? filterForm.outlet_id : undefined,
        item_type: filterForm.item_type !== '' ? filterForm.item_type : undefined,
        category_id: filterForm.category_id !== '' ? filterForm.category_id : undefined,
        stock_status: filterForm.stock_status !== '' ? filterForm.stock_status : undefined,
        is_active_only: filterForm.is_active_only === '1' ? '1' : undefined,
        in_stock_only: filterForm.in_stock_only === '1' ? '1' : undefined,
        page: 1,
    }

    router.get(route('inventories.stocks.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportCsv = () => {
    const query = {
        search: filterForm.search || undefined,
        outlet_id: filterForm.outlet_id !== '' ? filterForm.outlet_id : undefined,
        item_type: filterForm.item_type !== '' ? filterForm.item_type : undefined,
        category_id: filterForm.category_id !== '' ? filterForm.category_id : undefined,
        stock_status: filterForm.stock_status !== '' ? filterForm.stock_status : undefined,
        is_active_only: filterForm.is_active_only === '1' ? '1' : undefined,
        in_stock_only: filterForm.in_stock_only === '1' ? '1' : undefined,
    }

    router.get(route('inventories.stocks.export-csv'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportPdf = () => {
    const params = new URLSearchParams()
    if (filterForm.search) params.append('search', filterForm.search)
    if (filterForm.outlet_id !== '') params.append('outlet_id', filterForm.outlet_id)
    if (filterForm.item_type !== '') params.append('item_type', filterForm.item_type)
    if (filterForm.category_id !== '') params.append('category_id', filterForm.category_id)
    if (filterForm.stock_status !== '') params.append('stock_status', filterForm.stock_status)
    if (filterForm.is_active_only === '1') params.append('is_active_only', '1')
    if (filterForm.in_stock_only === '1') params.append('in_stock_only', '1')

    window.open(route('inventories.stocks.export-pdf-list') + '?' + params.toString(), '_blank')
}

const actionItems = computed(() => [
    {
        label: 'Ekspor Data Excel',
        icon: faFileExcel,
        iconClass: 'text-emerald-600',
        action: exportCsv,
    },
    {
        label: 'Ekspor Laporan PDF',
        icon: faFilePdf,
        iconClass: 'text-rose-600',
        action: exportPdf,
    },
    {
        divider: true,
    },
    {
        label: 'Impor Stok Massal',
        icon: faUpload,
        iconClass: 'text-blue-600',
        action: () => {
            showImportModal.value = true
        },
    },
    {
        divider: true,
    },
    {
        label: 'Pengaturan Metode Aset',
        icon: faSliders,
        iconClass: 'text-sky-600',
        action: () => {
            emit('open-costing-modal')
        },
    },
])
</script>
