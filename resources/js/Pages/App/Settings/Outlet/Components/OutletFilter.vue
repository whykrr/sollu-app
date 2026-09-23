<template>
    <ActionBar>
        <template #filters>
            <!-- Status Filter -->
            <FilterDropdown
                v-model="filterForm.is_active"
                label="Status"
                :options="statusOptions"
                :icon="faToggleOn"
                all-option-label="Semua Status"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama outlet, alamat, telepon..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <!-- Dual Mode View Switcher (Tabel vs Kartu) -->
            <div
                class="inline-flex items-center p-0.5 bg-slate-100 rounded-lg border border-slate-200 h-[30px]"
            >
                <button
                    type="button"
                    class="h-6 px-2 flex items-center justify-center rounded-md text-xs transition-colors duration-150 cursor-pointer"
                    :class="
                        viewMode === 'table'
                            ? 'bg-white text-slate-800 shadow-xs font-semibold'
                            : 'text-slate-500 hover:text-slate-700'
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
                            ? 'bg-white text-slate-800 shadow-xs font-semibold'
                            : 'text-slate-500 hover:text-slate-700'
                    "
                    title="Tampilan Kartu"
                    @click="$emit('update:viewMode', 'grid')"
                >
                    <FontAwesomeIcon :icon="faGrip" class="text-xs" />
                </button>
            </div>
        </template>

        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                <span>Tambah Outlet Baru</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faGrip,
    faPlus,
    faTableList,
    faToggleOn,
} from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    viewMode: {
        type: String,
        default: 'table',
    },
})

defineEmits(['create', 'update:viewMode'])

const statusOptions = [
    { value: 'true', label: 'Aktif' },
    { value: 'false', label: 'Nonaktif' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    is_active: props.filters?.is_active ?? '',
})

watch(
    () => props.filters,
    newFilters => {
        filterForm.search = newFilters?.search ?? ''
        filterForm.is_active = newFilters?.is_active ?? ''
    },
    { deep: true }
)

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
        page: 1,
    }

    router.get(route('settings.outlets.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
