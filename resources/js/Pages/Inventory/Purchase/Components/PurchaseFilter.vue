<template>
    <div class="flex flex-wrap items-start gap-2">
        <!-- Search bar -->
        <div>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari Nomor PO..."
            />
        </div>

        <!-- Filter Button -->
        <div>
            <button
                type="button"
                class="btn btn-sm border border-gray-200 hover:border-gray-300 bg-white"
                @click="openModal"
            >
                <span>Filter</span>
                <FontAwesomeIcon :icon="faSliders" />
            </button>
        </div>

        <!-- Active Filter Badges -->
        <div class="flex-1 flex flex-wrap items-center gap-1.5">
            <FilterBadge v-if="filterForm.status !== ''" @remove="removeFilter('status')">
                Status: {{ statusLabel(filterForm.status) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.supplier_id !== ''" @remove="removeFilter('supplier_id')">
                Supplier: {{ getSupplierName(filterForm.supplier_id) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.outlet_id !== ''" @remove="removeFilter('outlet_id')">
                Outlet: {{ getOutletName(filterForm.outlet_id) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.start_date !== ''" @remove="removeFilter('start_date')">
                Dari: {{ filterForm.start_date }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.end_date !== ''" @remove="removeFilter('end_date')">
                Sampai: {{ filterForm.end_date }}
            </FilterBadge>
        </div>

        <!-- Filter Modal Overlay -->
        <FilterModal
            :show="showFilterModal"
            title="Filter PO"
            @close="closeModal"
            @reset="resetTempFilters"
            @apply="applyFilters"
        >
            <div class="space-y-4">
                <!-- Status Filter -->
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
                            <option value="">Semua</option>
                            <option value="draft">Draft</option>
                            <option value="ordered">Ordered</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Supplier Filter -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                        >
                            Supplier
                        </label>
                        <select
                            v-model="tempFilters.supplier_id"
                            class="form-input w-full rounded-lg border-gray-200"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="sup in suppliers"
                                :key="sup.id"
                                :value="sup.id"
                            >
                                {{ sup.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Outlet Filter -->
                    <div class="space-y-1">
                        <AsyncOutletDropdown
                            v-model="tempFilters.outlet_id"
                            placeholder="Semua Outlet"
                            @loaded="onOutletsLoaded"
                        />
                    </div>

                    <!-- Date Filter -->
                    <div class="flex gap-3">
                        <div class="space-y-1 flex-1">
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >
                                Dari Tanggal
                            </label>
                            <input
                                v-model="tempFilters.start_date"
                                type="date"
                                class="form-input w-full rounded-lg border-gray-200"
                            />
                        </div>
                        <div class="space-y-1 flex-1">
                            <label
                                class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >
                                Sampai Tanggal
                            </label>
                            <input
                                v-model="tempFilters.end_date"
                                type="date"
                                class="form-input w-full rounded-lg border-gray-200"
                            />
                            />
                        </div>
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
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';

const props = defineProps({
    filters: Object,
    suppliers: {
        type: Array,
        default: () => [],
    },
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    supplier_id: props.filters?.supplier_id ?? '',
    outlet_id: props.filters?.outlet_id ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    status: '',
    supplier_id: '',
    outlet_id: '',
    start_date: '',
    end_date: '',
});

const loadedOutlets = ref([]);

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

const openModal = () => {
    tempFilters.status = filterForm.status;
    tempFilters.supplier_id = filterForm.supplier_id;
    tempFilters.outlet_id = filterForm.outlet_id;
    tempFilters.start_date = filterForm.start_date;
    tempFilters.end_date = filterForm.end_date;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.status = '';
    tempFilters.supplier_id = '';
    tempFilters.outlet_id = '';
    tempFilters.start_date = '';
    tempFilters.end_date = '';
};

const applyFilters = () => {
    filterForm.status = tempFilters.status;
    filterForm.supplier_id = tempFilters.supplier_id;
    filterForm.outlet_id = tempFilters.outlet_id;
    filterForm.start_date = tempFilters.start_date;
    filterForm.end_date = tempFilters.end_date;
    showFilterModal.value = false;
    updateQuery();
};

const removeFilter = (key) => {
    if (key in filterForm) {
        filterForm[key] = '';
    }
    updateQuery();
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        supplier_id: filterForm.supplier_id || undefined,
        outlet_id: filterForm.outlet_id || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
        page: 1,
    };

    router.get(route('inventory.purchases.index'), query, {
        preserveState: true,
        preserveScroll: true,
    });
};

const statusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        ordered: 'Ordered',
        received: 'Received',
        cancelled: 'Cancelled',
    };
    return labels[status] || status;
};

const getSupplierName = (id) => {
    const sup = props.suppliers.find((s) => s.id === id);
    return sup ? sup.name : 'Unknown';
};

const getOutletName = (id) => {
    const out = loadedOutlets.value.find((o) => o.id == id);
    return out ? out.name : 'Unknown';
};
</script>
