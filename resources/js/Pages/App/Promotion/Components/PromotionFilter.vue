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

            <!-- Target Scope Filter -->
            <FilterDropdown
                v-model="filterForm.target_scope"
                label="Target"
                :options="targetScopeOptions"
                all-option-label="Semua Target"
                @change="updateQuery"
            />

            <!-- Discount Type Filter -->
            <FilterDropdown
                v-model="filterForm.discount_type"
                label="Tipe Diskon"
                :options="discountTypeOptions"
                all-option-label="Semua Tipe"
                @change="updateQuery"
            />

            <!-- Application Mode Filter -->
            <FilterDropdown
                v-model="filterForm.application_mode"
                label="Mode"
                :options="applicationModeOptions"
                all-option-label="Semua Mode"
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
                placeholder="Cari nama atau kode promo..."
                @clear="updateQuery"
            />
        </template>

        <template #create>
            <button
                v-can="'promo.create'"
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Promo Baru</span>
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
import { useEnum } from '@/Composable/useEnum'
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
const { getOptions } = useEnum()

const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        value: String(store.id),
        label: store.name,
    }))
)

const statusOptions = computed(() => getOptions('PromotionStatus'))
const targetScopeOptions = computed(() => getOptions('PromotionTargetScope'))
const discountTypeOptions = computed(() => getOptions('PromotionDiscountType'))
const applicationModeOptions = computed(() => getOptions('PromotionApplicationMode'))

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    target_scope: props.filters?.target_scope ?? props.filters?.target ?? '',
    discount_type: props.filters?.discount_type ?? props.filters?.promo_type ?? '',
    application_mode: props.filters?.application_mode ?? props.filters?.mode ?? '',
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
        target_scope: filterForm.target_scope || undefined,
        discount_type: filterForm.discount_type || undefined,
        application_mode: filterForm.application_mode || undefined,
        outlet: filterForm.outlet || undefined,
        page: 1,
    }

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
