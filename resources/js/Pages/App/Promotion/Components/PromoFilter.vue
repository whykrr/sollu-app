<template>
    <ActionBar>
        <template #filters>
            <!-- Status Filter -->
            <FilterDropdown
                v-model="filterForm.status"
                label="Status"
                :options="statusOptions"
                all-option-label="Semua Status"
                @change="updateQuery"
            />

            <!-- Promo Type Filter -->
            <FilterDropdown
                v-model="filterForm.promo_type"
                label="Tipe Diskon"
                :options="promoTypeOptions"
                all-option-label="Semua Tipe"
                @change="updateQuery"
            />

            <!-- Target Type Filter -->
            <FilterDropdown
                v-model="filterForm.target_type"
                label="Target"
                :options="targetTypeOptions"
                all-option-label="Semua Target"
                @change="updateQuery"
            />

            <!-- Outlet Filter -->
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="filterForm.outlet"
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
                placeholder="Cari promo..."
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
                <span>Buat Promo</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faStore } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

defineEmits(['create'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const { outlets: userOutlets, selectedOutlet } = useAuth()
const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const statusOptions = [
    { value: 'draft', label: 'Draf' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
    { value: 'expired', label: 'Kedaluwarsa' },
]

const promoTypeOptions = [
    { value: 'percentage', label: 'Persentase (%)' },
    { value: 'fixed', label: 'Nominal Tetap (Rp)' },
]

const targetTypeOptions = [
    { value: 'product', label: 'Per Produk' },
    { value: 'bill', label: 'Per Bill' },
]

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    promo_type: props.filters?.promo_type ?? '',
    target_type: props.filters?.target_type ?? '',
    outlet: props.filters?.outlet ? String(props.filters.outlet) : '',
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
        status: filterForm.status || undefined,
        promo_type: filterForm.promo_type || undefined,
        target_type: filterForm.target_type || undefined,
        outlet: filterForm.outlet || undefined,
        page: 1,
    }

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
