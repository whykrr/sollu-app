<template>
    <FilterBar>
        <template #left>
            <FilterSegmented
                v-model="filterForm.status"
                :options="statusOptions"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nomor invoice, merchant..."
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

const filterForm = reactive({
    search: typeof props.filters?.search === 'string' ? props.filters.search : '',
    status: typeof props.filters?.status === 'string' ? props.filters.status : '',
    sort:
        typeof props.filters?.sort === 'string' && props.filters.sort
            ? props.filters.sort
            : 'created_at',
    direction:
        typeof props.filters?.direction === 'string' && props.filters.direction
            ? props.filters.direction
            : 'desc',
})

const statusOptions = [
    { value: '', label: 'Semua' },
    { value: 'pending', label: 'Menunggu Verifikasi' },
    { value: 'paid', label: 'Lunas' },
    { value: 'rejected', label: 'Ditolak' },
]

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        sort: filterForm.sort || undefined,
        direction: filterForm.direction || undefined,
        page: 1,
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

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)
</script>
