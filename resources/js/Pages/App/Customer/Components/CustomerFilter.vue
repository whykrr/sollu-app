<template>
    <ActionBar>
        <template #filters>
            <FilterSegmented
                v-model="filterForm.is_active"
                :options="statusOptions"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari pelanggan (nama, telp, email)..."
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
                dusk="create-customer-button"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                <span>Tambah Pelanggan</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faDownload, faUpload } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['create', 'open-import'])

const filterForm = reactive({
    search: props.filters?.search || '',
    is_active:
        props.filters?.is_active !== undefined && props.filters?.is_active !== null
            ? String(props.filters.is_active)
            : '',
})

const statusOptions = [
    { value: '', label: 'Semua Pelanggan' },
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Tidak Aktif' },
]

// Watch search input to trigger query
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

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportCsv = () => {
    router.get(
        route('customers.export', filterForm),
        {},
        { preserveScroll: true, preserveState: true }
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
