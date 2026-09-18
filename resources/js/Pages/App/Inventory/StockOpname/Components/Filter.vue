<template>
    <FilterBar>
        <template #left>
            <!-- Date Preset & Range -->
            <FilterPresetDate
                v-model="filterForm.preset"
                v-model:start-date="filterForm.date_from"
                v-model:end-date="filterForm.date_to"
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
                placeholder="Cari no. opname..."
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
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
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
    { value: 'in_progress', label: 'Sedang Berjalan' },
    { value: 'pending_approval', label: 'Menunggu Persetujuan' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    preset: props.filters?.preset ?? 'this_month',
    status: props.filters?.status ?? '',
    outlet_id: props.filters?.outlet_id ? String(props.filters.outlet_id) : '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
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
        status: filterForm.status !== '' ? filterForm.status : undefined,
        outlet_id: filterForm.outlet_id !== '' ? filterForm.outlet_id : undefined,
        date_from: filterForm.date_from !== '' ? filterForm.date_from : undefined,
        date_to: filterForm.date_to !== '' ? filterForm.date_to : undefined,
        page: 1,
    }

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
