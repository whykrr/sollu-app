<template>
    <ActionBar>
        <template #filters>
            <!-- Date Preset & Range -->
            <FilterPresetDate
                v-model="filterForm.preset"
                v-model:start-date="filterForm.start_date"
                v-model:end-date="filterForm.end_date"
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

            <!-- Channel Filter -->
            <FilterDropdown
                v-model="filterForm.channel"
                label="Channel"
                :options="channelOptions"
                all-option-label="Semua Channel"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari no. struk atau pelanggan..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <ActionsDropdown v-if="canExport" label="Opsi Data" :items="actionItems" />
        </template>

        <template #create>
            <button
                v-if="canCreate"
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Penjualan</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFileCsv, faPlus } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const emit = defineEmits(['create', 'export-csv'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    canCreate: {
        type: Boolean,
        default: true,
    },
    canExport: {
        type: Boolean,
        default: true,
    },
})

const actionItems = computed(() => [
    {
        label: 'Ekspor CSV',
        icon: faFileCsv,
        handler: () => emit('export-csv'),
    },
])

const statusOptions = [
    { value: 'draft', label: 'Draf' },
    { value: 'unpaid', label: 'Belum Lunas' },
    { value: 'paid', label: 'Lunas' },
    { value: 'cancel', label: 'Dibatalkan' },
]

const channelOptions = [
    { value: 'e_commerce', label: 'E-Commerce' },
    { value: 'social_media', label: 'Social Media' },
    { value: 'direct', label: 'Direct / B2B' },
    { value: 'wholesale', label: 'Wholesale' },
    { value: 'custom', label: 'Custom' },
    { value: 'dine_in', label: 'POS - Dine In' },
    { value: 'take_away', label: 'POS - Take Away' },
    { value: 'online_delivery', label: 'POS - Online Delivery' },
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
