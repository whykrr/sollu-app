<template>
    <FilterBar>
        <template #left>
            <!-- Status Filter -->
            <FilterDropdown
                v-model="filterForm.status"
                label="Status"
                :options="statusOptions"
                all-option-label="Semua Status"
                @change="updateQuery"
            />

            <!-- From Outlet Filter -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.from_outlet_id"
                label="Dari Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Asal"
                @change="updateQuery"
            />

            <!-- To Outlet Filter -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.to_outlet_id"
                label="Ke Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Tujuan"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari no. transfer..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { faStore } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const { outlets: userOutlets, selectedOutlet } = useAuth()
const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const statusOptions = [
    { value: 'pending', label: 'Menunggu' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'in_transit', label: 'Dalam Perjalanan' },
    { value: 'completed', label: 'Selesai' },
    { value: 'rejected', label: 'Ditolak' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    from_outlet_id: props.filters?.from_outlet_id ? String(props.filters.from_outlet_id) : '',
    to_outlet_id: props.filters?.to_outlet_id ? String(props.filters.to_outlet_id) : '',
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
        status: filterForm.status !== '' ? filterForm.status : undefined,
        from_outlet_id: filterForm.from_outlet_id !== '' ? filterForm.from_outlet_id : undefined,
        to_outlet_id: filterForm.to_outlet_id !== '' ? filterForm.to_outlet_id : undefined,
        page: 1,
    }

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
