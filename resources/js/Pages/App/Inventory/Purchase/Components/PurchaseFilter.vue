<template>
    <ActionBar>
        <template #filters>
            <!-- Preset Tanggal Terstandarisasi -->
            <FilterPresetDate
                v-model="filterForm.preset"
                v-model:start-date="filterForm.start_date"
                v-model:end-date="filterForm.end_date"
                @change="updateQuery"
            />

            <!-- Dropdown Status PO -->
            <FilterDropdown
                v-model="filterForm.status"
                label="Status"
                :options="statusOptions"
                :icon="faTag"
                all-option-label="Semua Status"
                @change="updateQuery"
            />

            <!-- Dropdown Pemasok -->
            <FilterDropdown
                v-if="supplierOptions.length > 0"
                v-model="filterForm.supplier_id"
                label="Pemasok"
                :options="supplierOptions"
                :icon="faTruck"
                all-option-label="Semua Pemasok"
                @change="updateQuery"
            />

            <!-- Dropdown Outlet -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.outlet_id"
                label="Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Outlet"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nomor PO / referensi..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <ActionsDropdown label="Opsi Data" :items="toolItems" />
        </template>

        <template #create>
            <!-- Jika memiliki fitur PO: gunakan dropdown action (+ Pembelian) -->
            <ActionsDropdown
                v-if="hasPOFeature"
                label="Pembelian Baru"
                :icon="faPlus"
                :items="createActionItems"
            />

            <!-- Jika TIDAK memiliki fitur PO: tombol Beli Langsung biasa (tanpa dropdown) -->
            <button
                v-else
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create-direct')"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Pembelian Baru</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPlus,
    faStore,
    faTruck,
    faFileCsv,
    faBolt,
    faTag,
    faFileInvoice,
} from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'
import { usePlanFeature } from '@/Composable/usePlanFeature'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'

const emit = defineEmits(['create', 'create-direct', 'export-csv'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
})

const { outlets: userOutlets, selectedOutlet } = useAuth()
const { enums } = useEnum()
const { hasFeature } = usePlanFeature()

const hasPOFeature = computed(() => {
    return hasFeature(enums.FeatureEnum?.PURCHASE_ORDERS || 'purchase_orders')
})

const outletOptions = computed(() => {
    const list = props.outlets?.length > 0 ? props.outlets : userOutlets.value || []
    return list.map(store => ({
        value: String(store.id),
        label: store.name,
    }))
})

const supplierOptions = computed(() => {
    return props.suppliers.map(sup => ({
        value: String(sup.id),
        label: sup.name,
    }))
})

const statusOptions = computed(() => [
    { value: enums.PurchaseOrderStatus?.Draft || 'draft', label: 'Draf' },
    { value: enums.PurchaseOrderStatus?.Ordered || 'ordered', label: 'Dipesan' },
    {
        value: enums.PurchaseOrderStatus?.PartialReceived || 'partial_received',
        label: 'Diterima Sebagian',
    },
    { value: enums.PurchaseOrderStatus?.Received || 'received', label: 'Selesai' },
    { value: enums.PurchaseOrderStatus?.Cancelled || 'cancelled', label: 'Dibatalkan' },
])

const activeFilters = computed(() => props.params || props.filters || {})

const filterForm = reactive({
    search: activeFilters.value.search ?? '',
    preset: activeFilters.value.preset ?? 'this_month',
    status: activeFilters.value.status ?? '',
    supplier_id: activeFilters.value.supplier_id ? String(activeFilters.value.supplier_id) : '',
    outlet_id: activeFilters.value.outlet_id ? String(activeFilters.value.outlet_id) : '',
    start_date: activeFilters.value.start_date ?? '',
    end_date: activeFilters.value.end_date ?? '',
})

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 400)
)

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        preset: filterForm.preset !== 'this_month' ? filterForm.preset : undefined,
        status: filterForm.status || undefined,
        supplier_id: filterForm.supplier_id || undefined,
        outlet_id: filterForm.outlet_id || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
        page: 1,
    }

    router.get(route('inventory.purchases.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportCsv = () => {
    emit('export-csv', {
        search: filterForm.search || undefined,
        preset: filterForm.preset,
        status: filterForm.status || undefined,
        supplier_id: filterForm.supplier_id || undefined,
        outlet_id: filterForm.outlet_id || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
    })
}

const createActionItems = computed(() => [
    {
        label: 'Pesanan Pembelian (PO Draf)',
        description: 'Buat draf pemesanan barang ke pemasok',
        icon: faFileInvoice,
        action: () => emit('create'),
    },
    {
        label: 'Beli Langsung (Direct Purchase)',
        description: 'Catat pembelian dan langsung terima stok fisik',
        icon: faBolt,
        action: () => emit('create-direct'),
    },
])

const toolItems = computed(() => [
    {
        label: 'Ekspor Riwayat Pembelian (CSV)',
        icon: faFileCsv,
        action: exportCsv,
    },
])
</script>
