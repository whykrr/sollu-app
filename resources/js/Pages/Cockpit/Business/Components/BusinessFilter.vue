<template>
    <FilterBar>
        <template #left>
            <!-- Status Segmented -->
            <FilterSegmented
                v-model="filterForm.status"
                :options="statusOptions"
                @change="updateQuery"
            />

            <!-- Business Type Dropdown -->
            <FilterDropdown
                v-if="businessTypeOptions.length > 0"
                v-model="filterForm.business_type_id"
                label="Jenis Bisnis"
                :options="businessTypeOptions"
                all-option-label="Semua Jenis Bisnis"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama bisnis, email, pemilik..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    businessTypes: {
        type: Array,
        default: () => [],
    },
})

const filterForm = reactive({
    search: typeof props.filters?.search === 'string' ? props.filters.search : '',
    status: typeof props.filters?.status === 'string' ? props.filters.status : '',
    business_type_id:
        typeof props.filters?.business_type_id === 'string' ? props.filters.business_type_id : '',
    sort:
        typeof props.filters?.sort === 'string' && props.filters.sort
            ? props.filters.sort
            : 'created_at',
})

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'active', label: 'Aktif' },
    { value: 'suspended', label: 'Ditangguhkan' },
]

const businessTypeOptions = computed(() => {
    return props.businessTypes.map(bt => ({
        value: String(bt.id),
        label: bt.name,
    }))
})

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        business_type_id: filterForm.business_type_id || undefined,
        sort: filterForm.sort || undefined,
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
