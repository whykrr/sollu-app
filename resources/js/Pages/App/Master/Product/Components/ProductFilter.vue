<template>
    <ActionBar>
        <template #filters>
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

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama atau kode barang..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <div
                class="inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 h-[30px]"
            >
                <button
                    type="button"
                    class="h-6 px-2 flex items-center justify-center rounded-md text-xs transition-colors duration-150 cursor-pointer"
                    :class="
                        viewMode === 'table'
                            ? 'bg-white text-neutral-900 font-medium border border-slate-200/80 shadow-none'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    title="Tampilan Tabel"
                    @click="$emit('update:viewMode', 'table')"
                >
                    <FontAwesomeIcon :icon="faTableList" class="text-xs" />
                </button>
                <button
                    type="button"
                    class="h-6 px-2 flex items-center justify-center rounded-md text-xs transition-colors duration-150 cursor-pointer"
                    :class="
                        viewMode === 'grid'
                            ? 'bg-white text-neutral-900 font-medium border border-slate-200/80 shadow-none'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    title="Tampilan Grid"
                    @click="$emit('update:viewMode', 'grid')"
                >
                    <FontAwesomeIcon :icon="faBorderAll" class="text-xs" />
                </button>
            </div>

            <ActionsDropdown label="Opsi" :items="actionItems" />
        </template>

        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                <span>Barang Baru</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBox,
    faStore,
    faDownload,
    faUpload,
    faTableList,
    faBorderAll,
    faPlus,
} from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'
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
    viewMode: {
        type: String,
        default: 'table',
    },
})

const emit = defineEmits(['open-import', 'update:viewMode', 'create'])

const { outlets: userOutlets, selectedOutlet } = useAuth()
const outletOptions = computed(() => {
    return (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
})

const categoryOptions = computed(() => {
    return props.categories.map(c => ({
        value: String(c.value ?? c.id),
        label: c.label ?? c.name,
    }))
})

const statusSegmentOptions = [
    { value: '', label: 'Semua Produk' },
    { value: '1', label: 'Sampah' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ?? '',
    category: props.filters?.category ?? '',
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

const actionItems = computed(() => [
    {
        label: 'Ekspor Data CSV',
        icon: faDownload,
        iconClass: 'text-emerald-600',
        action: exportCsv,
    },
    {
        label: 'Impor Data Massal',
        icon: faUpload,
        iconClass: 'text-blue-600',
        action: () => emit('open-import'),
    },
])
</script>
