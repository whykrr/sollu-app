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
                placeholder="Cari nama, email, alamat supplier..."
                @clear="updateQuery"
            />
        </template>

        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Supplier Baru</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

defineEmits(['create'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const statusOptions = [
    { value: '', label: 'Semua Supplier' },
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Nonaktif' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    is_active:
        props.filters?.is_active !== undefined && props.filters?.is_active !== null
            ? String(props.filters.is_active)
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
        is_active: filterForm.is_active !== '' ? filterForm.is_active : undefined,
        page: 1,
    }

    router.get(route('inventory.suppliers.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
