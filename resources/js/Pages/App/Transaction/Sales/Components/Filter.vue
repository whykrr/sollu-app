<template>
    <div class="space-y-3">
        <ActionBar>
            <template #filters>
                <!-- Date Preset & Range -->
                <FilterPresetDate
                    v-model="filterForm.preset"
                    v-model:start-date="filterForm.start_date"
                    v-model:end-date="filterForm.end_date"
                    @change="updateQuery"
                />

                <!-- B2B Channel Filter (Strictly Wholesale & Direct Sales) -->
                <FilterDropdown
                    v-model="filterForm.channel"
                    label="Saluran"
                    :options="channelOptions"
                    all-option-label="Semua Saluran"
                    @change="updateQuery"
                />
            </template>

            <template #search>
                <FilterSearch
                    v-model="filterForm.search"
                    placeholder="Cari no. faktur / transaksi atau pelanggan..."
                    @clear="updateQuery"
                />
            </template>

            <template #create>
                <button
                    v-if="canCreate"
                    type="button"
                    class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                    @click="$emit('create')"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Faktur Baru</span>
                </button>
            </template>
        </ActionBar>

        <!-- Status Filter Segmented -->
        <div class="flex items-center">
            <FilterSegmented
                :model-value="filterForm.status"
                :options="statusOptions"
                @update:model-value="onStatusChanged"
            />
        </div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'

defineEmits(['create'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    canCreate: {
        type: Boolean,
        default: true,
    },
})

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'draft', label: 'Draf' },
    { value: 'unpaid', label: 'Belum Dibayar' },
    { value: 'partial', label: 'Dibayar Sebagian' },
    { value: 'paid', label: 'Lunas' },
    { value: 'cancel', label: 'Batal' },
]

const channelOptions = [
    { value: 'wholesale', label: 'Grosir (Wholesale)' },
    { value: 'direct', label: 'Penjualan Langsung (Direct Sales)' },
]

const filterForm = reactive({
    search: props.filters.search || '',
    preset: props.filters.preset || 'this_month',
    status: props.filters.status || '',
    channel: props.filters.channel || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || '',
})

const onStatusChanged = val => {
    filterForm.status = val
    updateQuery()
}

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 400)
)

const updateQuery = () => {
    const query = {
        ...route().params,
        ...filterForm,
        page: 1,
    }

    if (query.preset === 'this_month') {
        delete query.preset
    }

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
