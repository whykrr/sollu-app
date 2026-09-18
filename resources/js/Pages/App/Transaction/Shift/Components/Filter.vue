<template>
    <FilterBar>
        <template #left>
            <!-- Status Segmented Filter -->
            <FilterSegmented
                v-model="filterForm.status"
                :options="statusOptions"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama kasir..."
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
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const statusOptions = [
    { value: '', label: 'Semua Shift' },
    { value: 'open', label: 'Buka' },
    { value: 'closed', label: 'Tutup' },
]

const filterForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || '',
})

const updateQuery = () => {
    const query = {
        ...route().params,
        ...filterForm,
        page: 1, // Reset to page 1 on filter
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

// Watch search with debounce
watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)
</script>
