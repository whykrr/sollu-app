<template>
    <ActionBar>
        <template #filters>
            <!-- Date Preset & Range -->
            <FilterPresetDate
                v-model="filterForm.preset"
                v-model:start-date="filterForm.date_from"
                v-model:end-date="filterForm.date_to"
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

            <!-- Reason Filter -->
            <FilterDropdown
                v-model="filterForm.reason"
                label="Alasan"
                :options="reasonOptions"
                all-option-label="Semua Alasan"
                @change="updateQuery"
            />

            <!-- Outlet Filter: HANYA jika outlet user > 1 DAN tidak ada selectedOutlet aktif di sidebar -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.outlet_id"
                label="Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Outlet"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari no. penyesuaian..."
                @clear="updateQuery"
            />
        </template>

        <template #tools>
            <ActionsDropdown v-if="toolItems.length > 0" label="Opsi" :items="toolItems" />
        </template>

        <template #create>
            <button
                v-if="can('inventory.adjustment.create')"
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                <span>Penyesuaian Baru</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faStore, faLock, faPlus } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['create', 'freeze'])

const { outlets: userOutlets, selectedOutlet, can } = useAuth()
const { getOptions } = useEnum()

const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const statusOptions = computed(() => getOptions('AdjustmentStatus'))
const reasonOptions = computed(() => getOptions('AdjustmentReason'))

const toolItems = computed(() => {
    const items = []
    if (can('inventory.adjustment.freeze')) {
        items.push({
            label: 'Kelola Pembekuan Stok',
            description: 'Kunci atau buka pergerakan stok per outlet',
            icon: faLock,
            action: () => emit('freeze'),
        })
    }
    return items
})

const filterForm = reactive({
    preset: props.filters?.preset || 'this_month',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    status: props.filters?.status || '',
    reason: props.filters?.reason || '',
    outlet_id: props.filters?.outlet_id ? String(props.filters.outlet_id) : '',
    search: props.filters?.search || '',
})

// Watch search with debounce
watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 400)
)

const updateQuery = () => {
    const query = {
        ...route().params,
        preset: filterForm.preset !== 'this_month' ? filterForm.preset : undefined,
        date_from: filterForm.date_from || undefined,
        date_to: filterForm.date_to || undefined,
        status: filterForm.status || undefined,
        reason: filterForm.reason || undefined,
        outlet_id: filterForm.outlet_id !== '' ? filterForm.outlet_id : undefined,
        search: filterForm.search || undefined,
        page: 1,
    }

    router.get(route('inventory.adjustments.index'), query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
