<template>
    <FilterBar>
        <template #left>
            <!-- Track Inventory Segmented / Filter -->
            <FilterSegmented
                v-model="filterForm.track_inventory"
                :options="trackInventoryOptions"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama bahan, SKU, barcode..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const trackInventoryOptions = [
    { value: '', label: 'Semua Bahan' },
    { value: '1', label: 'Lacak Stok' },
    { value: '0', label: 'Tanpa Lacak' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    track_inventory:
        props.filters?.track_inventory !== undefined && props.filters?.track_inventory !== null
            ? String(props.filters.track_inventory)
            : '',
})

// Watch search separately for immediate query trigger
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
        track_inventory: filterForm.track_inventory !== '' ? filterForm.track_inventory : undefined,
        page: 1,
    }

    router.get(route('inventory.raw-materials.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
