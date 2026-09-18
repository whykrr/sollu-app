<template>
    <FilterBar>
        <template #left>
            <!-- Status Filter -->
            <FilterDropdown
                v-model="filterForm.status"
                label="Status Pembayaran"
                :options="statusOptions"
                all-option-label="Semua Status"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nomor invoice..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
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

const filterForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
})

const statusOptions = computed(() => {
    return getOptions('InvoiceStatus')
})

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
        status: filterForm.status || undefined,
        page: 1,
    }

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
