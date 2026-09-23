<template>
    <ActionBar>
        <template #filters>
            <!-- Outlet Filter (hanya jika multi-outlet dan sidebar di "Semua Outlet") -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.outlet"
                label="Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Outlet"
                @change="updateQuery"
            />

            <!-- Tipe Perangkat Filter -->
            <FilterDropdown
                v-model="filterForm.device_type"
                label="Tipe"
                :options="deviceTypeOptions"
                :icon="faDesktop"
                all-option-label="Semua Tipe"
                @change="updateQuery"
            />

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
                placeholder="Cari nama perangkat, serial..."
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
                <span>Tambah Perangkat</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faDesktop,
    faGrip,
    faPlus,
    faStore,
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
    outlets: {
        type: Array,
        default: () => [],
    },
    selectedOutlet: {
        type: Object,
        default: null,
    },
    viewMode: {
        type: String,
        default: 'table',
    },
})

defineEmits(['create', 'update:viewMode'])

const outletOptions = computed(() =>
    (props.outlets || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const deviceTypeOptions = [
    { value: 'pos_terminal', label: 'POS Terminal (Desktop)' },
    { value: 'pos_mobile', label: 'POS Mobile (Tablet/HP)' },
]

const statusOptions = [
    { value: 'true', label: 'Aktif' },
    { value: 'false', label: 'Nonaktif' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ? String(props.filters.outlet) : '',
    device_type: props.filters?.device_type ?? '',
    is_active: props.filters?.is_active ?? '',
})

watch(
    () => props.filters,
    newFilters => {
        filterForm.search = newFilters?.search ?? ''
        filterForm.outlet = newFilters?.outlet ? String(newFilters.outlet) : ''
        filterForm.device_type = newFilters?.device_type ?? ''
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
        outlet: filterForm.outlet || undefined,
        device_type: filterForm.device_type || undefined,
        is_active: filterForm.is_active !== '' ? filterForm.is_active : undefined,
        page: 1,
    }

    router.get(route('settings.devices.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
