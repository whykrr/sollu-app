<template>
    <FilterBar>
        <template #left>
            <!-- Date Preset & Range -->
            <FilterPresetDate
                v-model="filterForm.preset"
                v-model:start-date="filterForm.start_date"
                v-model:end-date="filterForm.end_date"
                @change="updateQuery"
            />

            <!-- Status Filter -->
            <FilterDropdown
                v-model="filterForm.status"
                label="Status"
                :options="statusOptions"
                all-option-label="Semua Status"
                @change="updateQuery"
            />

            <!-- Supplier Filter -->
            <FilterDropdown
                v-if="supplierOptions.length > 0"
                v-model="filterForm.supplier_id"
                label="Supplier"
                :options="supplierOptions"
                :icon="faTruck"
                all-option-label="Semua Supplier"
                @change="updateQuery"
            />

            <!-- Outlet Filter -->
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
                placeholder="Cari nomor PO..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { faStore, faTruck } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
})

const { outlets: userOutlets, selectedOutlet } = useAuth()
const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const supplierOptions = computed(() => {
    return props.suppliers.map(sup => ({
        value: String(sup.id),
        label: sup.name,
    }))
})

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'ordered', label: 'Ordered' },
    { value: 'received', label: 'Received' },
    { value: 'cancelled', label: 'Cancelled' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    preset: props.filters?.preset ?? 'this_month',
    status: props.filters?.status ?? '',
    supplier_id: props.filters?.supplier_id ? String(props.filters.supplier_id) : '',
    outlet_id: props.filters?.outlet_id ? String(props.filters.outlet_id) : '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
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
</script>
