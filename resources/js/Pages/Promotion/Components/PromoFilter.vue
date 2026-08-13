<template>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search bar -->
        <div>
            <FilterSearch v-model="filterForm.search" />
        </div>

        <!-- Filter Button -->
        <div>
            <button
                type="button"
                @click="openModal"
                class="btn btn-sm border border-gray-200 hover:border-gray-300 bg-white"
            >
                <span>Filter</span>
                <FontAwesomeIcon :icon="faSliders" />
            </button>
        </div>

        <!-- Active Filter Badges -->
        <div class="flex-1 flex flex-wrap items-center gap-1.5">
            <FilterBadge v-if="filterForm.status" @remove="removeFilter('status')">
                Status: {{ getStatusLabel(filterForm.status) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.promo_type" @remove="removeFilter('promo_type')">
                Tipe Diskon: {{ getPromoTypeLabel(filterForm.promo_type) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.target_type" @remove="removeFilter('target_type')">
                Target: {{ getTargetTypeLabel(filterForm.target_type) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.outlet" @remove="removeFilter('outlet')">
                Outlet: {{ getOutletLabel(filterForm.outlet) }}
            </FilterBadge>
        </div>

        <!-- Filter Modal Overlay -->
        <FilterModal
            :show="showFilterModal"
            title="Filter Promo"
            @close="closeModal"
            @reset="resetTempFilters"
            @apply="applyFilters"
        >
            <!-- Body -->
            <div class="space-y-4">
                <!-- Status Filter -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Status
                    </label>
                    <DropdownField
                        id="status"
                        v-model="tempFilters.status"
                        placeholder="Semua Status"
                        class="w-full"
                        :options="statusOptions"
                    />
                </div>

                <!-- Tipe Diskon Filter -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Tipe Diskon
                    </label>
                    <DropdownField
                        id="promo_type"
                        v-model="tempFilters.promo_type"
                        placeholder="Semua Tipe Diskon"
                        class="w-full"
                        :options="promoTypeOptions"
                    />
                </div>

                <!-- Target Filter -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Target Promo
                    </label>
                    <DropdownField
                        id="target_type"
                        v-model="tempFilters.target_type"
                        placeholder="Semua Target"
                        class="w-full"
                        :options="targetTypeOptions"
                    />
                </div>

                <!-- Outlet Filter -->
                <div v-if="outlets.length > 1 && selectedOutlet === null" class="space-y-1">
                    <AsyncOutletDropdown
                        id="outlets"
                        v-model="tempFilters.outlet"
                        label="Outlet"
                        placeholder="Semua Outlet"
                    />
                </div>
            </div>
        </FilterModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faSliders } from '@fortawesome/free-solid-svg-icons';
import DropdownField from '@/Components/Form/DropdownField.vue';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';

const props = defineProps({
    filters: Object,
});

const outlets = usePage().props.auth?.outlets?.map((store) => ({
    value: store.id,
    label: store.name,
})) || [];

const selectedOutlet = computed(() => usePage().props.selectedOutlet);

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    promo_type: props.filters?.promo_type ?? '',
    target_type: props.filters?.target_type ?? '',
    outlet: props.filters?.outlet ?? '',
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    status: '',
    promo_type: '',
    target_type: '',
    outlet: '',
});

const statusOptions = [
    { value: 'draft', label: 'Draf' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
    { value: 'expired', label: 'Kedaluwarsa' }
];

const promoTypeOptions = [
    { value: 'percentage', label: 'Persentase (%)' },
    { value: 'fixed', label: 'Nominal Tetap (Rp)' }
];

const targetTypeOptions = [
    { value: 'product', label: 'Per Produk' },
    { value: 'bill', label: 'Per Bill' }
];

// Watch search separately for immediate query trigger
watch(
    () => filterForm.search,
    debounce((newVal) => {
        updateQuery();
    }, 500),
);

const getStatusLabel = (val) => {
    return statusOptions.find((o) => o.value === val)?.label ?? val;
};

const getPromoTypeLabel = (val) => {
    return promoTypeOptions.find((o) => o.value === val)?.label ?? val;
};

const getTargetTypeLabel = (val) => {
    return targetTypeOptions.find((o) => o.value === val)?.label ?? val;
};

const getOutletLabel = (outId) => {
    return outlets.find((o) => o.value === outId)?.label ?? outId;
};

const openModal = () => {
    tempFilters.status = filterForm.status;
    tempFilters.promo_type = filterForm.promo_type;
    tempFilters.target_type = filterForm.target_type;
    tempFilters.outlet = filterForm.outlet;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.status = '';
    tempFilters.promo_type = '';
    tempFilters.target_type = '';
    tempFilters.outlet = '';
};

const applyFilters = () => {
    filterForm.status = tempFilters.status;
    filterForm.promo_type = tempFilters.promo_type;
    filterForm.target_type = tempFilters.target_type;
    filterForm.outlet = tempFilters.outlet;
    showFilterModal.value = false;
    updateQuery();
};

const removeFilter = (key) => {
    if (key === 'status') filterForm.status = '';
    if (key === 'promo_type') filterForm.promo_type = '';
    if (key === 'target_type') filterForm.target_type = '';
    if (key === 'outlet') filterForm.outlet = '';
    updateQuery();
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        promo_type: filterForm.promo_type || undefined,
        target_type: filterForm.target_type || undefined,
        outlet: filterForm.outlet || undefined,
        page: 1,
    };

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
