<template>
    <FilterBar>
        <template #left>
            <!-- Product Type Filter -->
            <FilterDropdown
                v-model="filterForm.product_type"
                label="Tipe"
                :options="productTypeOptions"
                :icon="faTag"
                all-option-label="Semua Tipe"
                @change="updateQuery"
            />

            <!-- Category Filter -->
            <FilterDropdown
                v-if="categoryOptions.length > 0"
                v-model="filterForm.category"
                label="Kategori"
                :options="categoryOptions"
                :icon="faBox"
                all-option-label="Semua Kategori"
                @change="updateQuery"
            />

            <!-- Outlet Filter (if multi-outlet) -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && selectedOutlet === null"
                v-model="filterForm.outlet"
                label="Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Outlet"
                @change="updateQuery"
            />

            <!-- Status Segmented / Trashed -->
            <FilterSegmented
                v-model="filterForm.is_deleted"
                :options="statusSegmentOptions"
                @change="updateQuery"
            />
        </template>

        <template #actions>
            <FilterActions>
                <button type="button" class="btn btn-flat btn-sm" @click="exportCsv">
                    <FontAwesomeIcon :icon="faDownload" class="text-xs text-neutral-500" />
                    <span>Ekspor</span>
                </button>

                <button type="button" class="btn btn-flat btn-sm" @click="$emit('open-import')">
                    <FontAwesomeIcon :icon="faUpload" class="text-xs text-neutral-500" />
                    <span>Impor</span>
                </button>
            </FilterActions>
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari produk / kode..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBox, faStore, faDownload, faUpload, faTag } from '@fortawesome/free-solid-svg-icons'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterActions from '@/Components/UI/Filter/FilterActions.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import { useAuth } from '@/Composable/useAuth'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

defineEmits(['open-import'])

const { outlets: userOutlets, selectedOutlet } = useAuth()
const outletOptions = computed(() => {
    return (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
})

const productTypeOptions = [
    { value: 'basic', label: 'Barang' },
    { value: 'service', label: 'Layanan' },
    { value: 'bundle', label: 'Bundle' },
]

const categoryOptions = computed(() => {
    return props.categories.map(c => ({
        value: String(c.value ?? c.id),
        label: c.label ?? c.name,
    }))
})

const statusSegmentOptions = [
    { value: '', label: 'Semua Produk' },
    { value: '1', label: 'Arsip' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ?? '',
    category: props.filters?.category ?? '',
    product_type: props.filters?.product_type ?? '',
    is_deleted: props.filters?.is_deleted ? '1' : '',
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
        search: filterForm.search || undefined,
        category: filterForm.category || undefined,
        outlet: filterForm.outlet || undefined,
        product_type: filterForm.product_type || undefined,
        is_deleted: filterForm.is_deleted === '1' ? 1 : undefined,
        page: 1,
    }

    router.get(route('master.products.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportCsv = () => {
    router.get(
        route('master.products.export', filterForm),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        }
    )
}
</script>
