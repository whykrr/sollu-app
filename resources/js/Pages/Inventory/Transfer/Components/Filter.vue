<template>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search bar -->
        <div>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari no. transfer..."
            />
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
            <FilterBadge
                v-if="filterForm.status !== ''"
                @remove="removeFilter('status')"
            >
                Status: {{ getStatusName(filterForm.status) }}
            </FilterBadge>
            <FilterBadge
                v-if="filterForm.from_outlet_id !== ''"
                @remove="removeFilter('from_outlet_id')"
            >
                Dari: {{ getOutletName(filterForm.from_outlet_id) }}
            </FilterBadge>
            <FilterBadge
                v-if="filterForm.to_outlet_id !== ''"
                @remove="removeFilter('to_outlet_id')"
            >
                Ke: {{ getOutletName(filterForm.to_outlet_id) }}
            </FilterBadge>
        </div>

        <!-- Filter Modal Overlay -->
        <FilterModal
            :show="showFilterModal"
            title="Filter Transfer Stok"
            @close="closeModal"
            @reset="resetTempFilters"
            @apply="applyFilters"
        >
            <div class="space-y-4">
                <div class="space-y-1">
                    <label
                        class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                    >
                        Status
                    </label>
                    <select
                        v-model="tempFilters.status"
                        class="form-input w-full rounded-lg border-gray-200"
                    >
                        <option value="">Semua Status</option>
                        <option
                            v-for="status in statusOptions"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label
                        class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                    >
                        Dari Outlet
                    </label>
                    <AsyncOutletDropdown
                        v-if="showFilterModal"
                        v-model="tempFilters.from_outlet_id"
                        placeholder="Semua Outlet"
                        @loaded="onOutletsLoaded"
                    />
                </div>

                <div class="space-y-1">
                    <label
                        class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                    >
                        Ke Outlet
                    </label>
                    <AsyncOutletDropdown
                        v-if="showFilterModal"
                        v-model="tempFilters.to_outlet_id"
                        placeholder="Semua Outlet"
                    />
                </div>
            </div>
        </FilterModal>
    </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faSliders } from '@fortawesome/free-solid-svg-icons';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';

const props = defineProps({
    filters: Object,
});

const loadedOutlets = ref([]);

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    from_outlet_id: props.filters?.from_outlet_id ?? '',
    to_outlet_id: props.filters?.to_outlet_id ?? '',
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    status: '',
    from_outlet_id: '',
    to_outlet_id: '',
});

const statusOptions = [
    { value: 'pending', label: 'Menunggu' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'in_transit', label: 'Dalam Perjalanan' },
    { value: 'completed', label: 'Selesai' },
    { value: 'rejected', label: 'Ditolak' },
];

const onOutletsLoaded = (outlets) => {
    loadedOutlets.value = outlets;
};

// Watch search separately for immediate query trigger
watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery();
    }, 500),
);

const getStatusName = (val) => {
    return statusOptions.find((o) => o.value == val)?.label || val;
};

const getOutletName = (id) => {
    return loadedOutlets.value.find((o) => o.id == id)?.name || id;
};

const openModal = () => {
    tempFilters.status = filterForm.status;
    tempFilters.from_outlet_id = filterForm.from_outlet_id;
    tempFilters.to_outlet_id = filterForm.to_outlet_id;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.status = '';
    tempFilters.from_outlet_id = '';
    tempFilters.to_outlet_id = '';
};

const applyFilters = () => {
    filterForm.status = tempFilters.status;
    filterForm.from_outlet_id = tempFilters.from_outlet_id;
    filterForm.to_outlet_id = tempFilters.to_outlet_id;
    showFilterModal.value = false;
    updateQuery();
};

const removeFilter = (key) => {
    filterForm[key] = '';
    updateQuery();
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        status: filterForm.status !== '' ? filterForm.status : undefined,
        from_outlet_id:
            filterForm.from_outlet_id !== ''
                ? filterForm.from_outlet_id
                : undefined,
        to_outlet_id:
            filterForm.to_outlet_id !== ''
                ? filterForm.to_outlet_id
                : undefined,
        page: 1,
    };

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
