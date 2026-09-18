<template>
    <FilterBar>
        <template #left>
            <!-- Date Preset & Range Filter -->
            <FilterPresetDate
                v-model="filterForm.preset"
                v-model:start-date="filterForm.start_date"
                v-model:end-date="filterForm.end_date"
                @change="updateQuery"
            />

            <!-- Status Dropdown Filter -->
            <FilterDropdown
                v-model="filterForm.status"
                label="Status"
                :options="statusOptions"
                all-option-label="Semua Status"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari no. shift atau nama kasir..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import { useEnum } from '@/Composable/useEnum'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const { getOptions } = useEnum()
const statusOptions = getOptions('ShiftStatus')

const filterForm = reactive({
    search: props.filters.search || '',
    preset: props.filters.preset || 'this_month',
    status: props.filters.status || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || '',
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
        ...filterForm,
        page: 1, // Reset to page 1 on filter
    }

    if (query.preset === 'this_month') {
        delete query.preset
    }

    // Clean up empty params
    Object.keys(query).forEach(key => {
        if (query[key] === '' || query[key] === null || query[key] === undefined) {
            delete query[key]
        }
    })

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
