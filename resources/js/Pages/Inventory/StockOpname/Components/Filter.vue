<template>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search bar -->
        <div>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari no. opname..."
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
            <div v-if="filterForm.status !== ''" class="filter-badge">
                <span>Status: {{ getStatusName(filterForm.status) }}</span>
                <button
                    type="button"
                    @click="removeFilter('status')"
                    class="filter-badge-remove"
                    title="Hapus filter"
                >
                    ✕
                </button>
            </div>
            <div v-if="filterForm.outlet_id !== ''" class="filter-badge">
                <span>Outlet: {{ getOutletName(filterForm.outlet_id) }}</span>
                <button
                    type="button"
                    @click="removeFilter('outlet_id')"
                    class="filter-badge-remove"
                    title="Hapus filter"
                >
                    ✕
                </button>
            </div>
            <div v-if="filterForm.date_from !== ''" class="filter-badge">
                <span>Dari: {{ filterForm.date_from }}</span>
                <button
                    type="button"
                    @click="removeFilter('date_from')"
                    class="filter-badge-remove"
                    title="Hapus filter"
                >
                    ✕
                </button>
            </div>
            <div v-if="filterForm.date_to !== ''" class="filter-badge">
                <span>Sampai: {{ filterForm.date_to }}</span>
                <button
                    type="button"
                    @click="removeFilter('date_to')"
                    class="filter-badge-remove"
                    title="Hapus filter"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Filter Modal Overlay -->
        <div v-show="showFilterModal" class="overlay-backdrop">
            <div class="overlay-modal max-w-md">
                <!-- Header -->
                <div class="overlay-header">
                    <h3 class="overlay-title">Filter Stock Opname</h3>
                    <button
                        type="button"
                        @click="closeModal"
                        class="overlay-close"
                    >
                        ✖
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-4">
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
                        <AsyncOutletDropdown
                            v-if="showFilterModal"
                            v-model="tempFilters.outlet_id"
                            placeholder="Semua Outlet"
                            @loaded="onOutletsLoaded"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="space-y-1">
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >
                                Dari Tanggal
                            </label>
                            <input
                                type="date"
                                v-model="tempFilters.date_from"
                                class="form-input w-full rounded-lg border-gray-200"
                            />
                        </div>
                        <div class="space-y-1">
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >
                                Sampai Tanggal
                            </label>
                            <input
                                type="date"
                                v-model="tempFilters.date_to"
                                class="form-input w-full rounded-lg border-gray-200"
                            />
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="overlay-footer">
                    <button
                        type="button"
                        class="btn btn-outline-main btn-sm rounded-lg"
                        @click="resetTempFilters"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-neutral-400 btn-sm rounded-lg"
                        @click="closeModal"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-highlight-main btn-sm rounded-lg"
                        @click="applyFilters"
                    >
                        Terapkan
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import { faSliders } from '@fortawesome/free-solid-svg-icons';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';

const props = defineProps({
    filters: Object,
});

const loadedOutlets = ref([]);

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    outlet_id: props.filters?.outlet_id ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    status: '',
    outlet_id: '',
    date_from: '',
    date_to: '',
});

const statusOptions = [
    { value: 'in_progress', label: 'Sedang Berjalan' },
    { value: 'pending_approval', label: 'Menunggu Persetujuan' },
    { value: 'approved', label: 'Disetujui' },
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
    tempFilters.outlet_id = filterForm.outlet_id;
    tempFilters.date_from = filterForm.date_from;
    tempFilters.date_to = filterForm.date_to;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.status = '';
    tempFilters.outlet_id = '';
    tempFilters.date_from = '';
    tempFilters.date_to = '';
};

const applyFilters = () => {
    filterForm.status = tempFilters.status;
    filterForm.outlet_id = tempFilters.outlet_id;
    filterForm.date_from = tempFilters.date_from;
    filterForm.date_to = tempFilters.date_to;
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
        outlet_id:
            filterForm.outlet_id !== '' ? filterForm.outlet_id : undefined,
        date_from:
            filterForm.date_from !== '' ? filterForm.date_from : undefined,
        date_to: filterForm.date_to !== '' ? filterForm.date_to : undefined,
        page: 1,
    };

    router.get(window.location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
