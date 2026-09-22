<template>
    <ActionBar>
        <template #filters>
            <!-- Trashed / Archived Filter Segmented -->
            <FilterSegmented
                v-model="filterForm.is_deleted"
                :options="statusSegmentOptions"
                @change="updateQuery"
            />

            <!-- Role Filter Dropdown -->
            <FilterDropdown
                v-if="roleOptions.length > 0"
                v-model="filterForm.role"
                label="Peran"
                :options="roleOptions"
                :icon="faUserShield"
                all-option-label="Semua Peran"
                @change="updateQuery"
            />

            <!-- Outlet Filter Dropdown -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.outlet"
                label="Outlet"
                :options="outletOptions"
                :icon="faMapMarkerAlt"
                all-option-label="Semua Outlet"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama, email, atau telepon..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <ActionsDropdown label="Opsi" :items="actionItems" />
        </template>

        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                <span>Tambah Pegawai</span>
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
    faUserShield,
    faMapMarkerAlt,
    faPlus,
    faDownload,
    faUpload,
} from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    roles: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['create', 'open-import'])

const { outlets: userOutlets, selectedOutlet } = useAuth()

const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const roleOptions = computed(() => {
    return (props.roles || []).map(r => ({
        value: String(r.value ?? r.id ?? r.name),
        label: r.label ?? r.name,
    }))
})

const statusSegmentOptions = [
    { value: '', label: 'Semua Pegawai' },
    { value: '1', label: 'Arsip' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ? String(props.filters.outlet) : '',
    role: props.filters?.role ? String(props.filters.role) : '',
    is_deleted: props.filters?.is_deleted ? '1' : '',
})

watch(
    () => props.filters,
    newFilters => {
        filterForm.search = newFilters?.search ?? ''
        filterForm.outlet = newFilters?.outlet ? String(newFilters.outlet) : ''
        filterForm.role = newFilters?.role ? String(newFilters.role) : ''
        filterForm.is_deleted = newFilters?.is_deleted ? '1' : ''
    },
    { deep: true }
)

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
        role: filterForm.role || undefined,
        outlet: filterForm.outlet || undefined,
        is_deleted: filterForm.is_deleted === '1' ? 1 : undefined,
        page: 1,
    }

    router.get(route('employees.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportExcel = () => {
    router.get(
        route('employees.export', {
            search: filterForm.search || undefined,
            role: filterForm.role || undefined,
            outlet: filterForm.outlet || undefined,
            is_deleted: filterForm.is_deleted === '1' ? 1 : undefined,
        }),
        {},
        { preserveScroll: true, preserveState: true }
    )
}

const actionItems = computed(() => [
    {
        label: 'Ekspor Data Excel',
        icon: faDownload,
        iconClass: 'text-emerald-600',
        action: exportExcel,
    },
    {
        label: 'Impor Data Massal',
        icon: faUpload,
        iconClass: 'text-blue-600',
        action: () => emit('open-import'),
    },
])
</script>
