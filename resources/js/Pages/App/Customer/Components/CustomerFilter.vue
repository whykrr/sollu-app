<template>
    <FilterBar>
        <template #left>
            <FilterSegmented
                v-model="filterForm.is_active"
                :options="statusOptions"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari pelanggan (nama, telp, email)..."
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

const filterForm = reactive({
    search: props.filters?.search || '',
    is_active:
        props.filters?.is_active !== undefined && props.filters?.is_active !== null
            ? String(props.filters.is_active)
            : '',
})

const statusOptions = [
    { value: '', label: 'Semua Pelanggan' },
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Tidak Aktif' },
]

// Watch search input to trigger query
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
        is_active: filterForm.is_active !== '' ? filterForm.is_active : undefined,
        page: 1, // Reset page
    }

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
