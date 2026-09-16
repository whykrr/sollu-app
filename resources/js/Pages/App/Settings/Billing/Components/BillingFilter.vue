<template>
    <div class="flex items-center gap-2">
        <FilterSearch v-model="filterForm.search" placeholder="Cari nomor invoice..." />

        <!-- Filter Modal Toggle -->
        <button class="btn btn-flat btn-sm bg-white shrink-0" @click="showFilterModal = true">
            <FontAwesomeIcon :icon="faSliders" />
            <span class="hidden md:inline">Filter</span>
        </button>

        <FilterBadge
            v-if="
                filterForm.status !== '' &&
                filterForm.status !== null &&
                filterForm.status !== undefined
            "
            @remove="filterForm.status = ''"
        >
            Status:
            {{ statusOptions.find(o => o.value === filterForm.status)?.label || filterForm.status }}
        </FilterBadge>

        <FilterModal
            :show="showFilterModal"
            title="Filter Invoice"
            @close="showFilterModal = false"
            @apply="applyFilters"
            @reset="resetFilters"
        >
            <div class="space-y-3">
                <DropdownField
                    v-model="tempFilters.status"
                    label="Status Pembayaran"
                    :options="statusOptions"
                />
            </div>
        </FilterModal>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSliders } from '@fortawesome/free-solid-svg-icons'
import { debounce } from 'lodash'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue'
import FilterModal from '@/Components/UI/Filter/FilterModal.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import { useEnum } from '@/Composable/useEnum'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const { getOptions } = useEnum()

const filterForm = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
})

const tempFilters = ref({
    status: filterForm.value.status,
})

const showFilterModal = ref(false)

const statusOptions = computed(() => {
    return [{ value: '', label: 'Semua Status' }, ...getOptions('InvoiceStatus')]
})

watch(
    () => filterForm.value.search,
    debounce(() => {
        updateQuery()
    }, 500)
)

watch(
    () => filterForm.value.status,
    () => {
        updateQuery()
    }
)

const applyFilters = () => {
    filterForm.value.status = tempFilters.value.status
    showFilterModal.value = false
}

const resetFilters = () => {
    filterForm.value.status = ''
    filterForm.value.search = ''
    tempFilters.value.status = ''
    showFilterModal.value = false
}

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.value.search || undefined,
        status: filterForm.value.status || undefined,
        page: 1,
    }

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
