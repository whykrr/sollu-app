<template>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search bar -->
        <div>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama, sku, barcode..."
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
            <!-- Track Inventory Badge -->
            <div v-if="filterForm.track_inventory !== ''" class="filter-badge">
                <span
                    >Lacak Stok:
                    {{
                        filterForm.track_inventory == '1' ? 'Ya' : 'Tidak'
                    }}</span
                >
                <button
                    type="button"
                    @click="removeFilter('track_inventory')"
                    class="filter-badge-remove"
                    title="Hapus filter"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Filter Modal Overlay -->
        <div v-if="showFilterModal" class="overlay-backdrop">
            <div class="overlay-modal max-w-md">
                <!-- Header -->
                <div class="overlay-header">
                    <h3 class="overlay-title">Filter Bahan Baku</h3>
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
                    <!-- Track Inventory Filter -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >Lacak Stok</label
                        >
                        <select
                            v-model="tempFilters.track_inventory"
                            class="form-input w-full rounded-lg border-gray-200"
                        >
                            <option value="">Semua</option>
                            <option value="1">Ya</option>
                            <option value="0">Tidak</option>
                        </select>
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
import { faSliders } from '@fortawesome/free-solid-svg-icons';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';

const props = defineProps({
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    track_inventory: props.filters?.track_inventory ?? '',
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    track_inventory: '',
});

// Watch search separately for immediate query trigger
watch(
    () => filterForm.search,
    debounce((newVal) => {
        updateQuery();
    }, 500),
);

const openModal = () => {
    tempFilters.track_inventory = filterForm.track_inventory;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.track_inventory = '';
};

const applyFilters = () => {
    filterForm.track_inventory = tempFilters.track_inventory;
    showFilterModal.value = false;
    updateQuery();
};

const removeFilter = (key) => {
    if (key === 'track_inventory') filterForm.track_inventory = '';
    updateQuery();
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        track_inventory:
            filterForm.track_inventory !== ''
                ? filterForm.track_inventory
                : undefined,
        page: 1,
    };

    router.get(route('inventory.raw-materials.index'), query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
