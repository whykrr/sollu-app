<template>
    <ActionBar>
        <template #filters>
            <!-- Track Inventory Segmented / Filter -->
            <FilterSegmented
                v-model="filterForm.track_inventory"
                :options="trackInventoryOptions"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama bahan, SKU, barcode..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <ActionsDropdown label="Opsi Data" :items="actionItems" />
        </template>

        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Baru</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFileExport, faFileImport, faPlus } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const emit = defineEmits(['create', 'export-csv', 'open-import'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const actionItems = computed(() => [
    {
        label: 'Ekspor CSV',
        icon: faFileExport,
        handler: () => emit('export-csv'),
    },
    {
        label: 'Impor CSV',
        icon: faFileImport,
        handler: () => emit('open-import'),
    },
])

const trackInventoryOptions = [
    { value: '', label: 'Semua Bahan' },
    { value: '1', label: 'Lacak Stok' },
    { value: '0', label: 'Tanpa Lacak' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    track_inventory:
        props.filters?.track_inventory !== undefined && props.filters?.track_inventory !== null
            ? String(props.filters.track_inventory)
            : '',
})

// Watch search separately for immediate query trigger
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
        track_inventory: filterForm.track_inventory !== '' ? filterForm.track_inventory : undefined,
        page: 1,
    }

    router.get(route('inventory.raw-materials.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
