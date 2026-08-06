<template>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search bar -->
        <div>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama produk, SKU, barcode..."
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
            <FilterBadge v-if="filterForm.outlet_id !== ''" @remove="removeFilter('outlet_id')">
                Outlet: {{ getOutletName(filterForm.outlet_id) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.item_type !== ''" @remove="removeFilter('item_type')">
                Tipe: {{ filterForm.item_type == 'raw_material' ? 'Bahan Baku' : 'Produk' }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.category_id !== ''" @remove="removeFilter('category_id')">
                Kategori: {{ getCategoryName(filterForm.category_id) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.stock_status !== ''" @remove="removeFilter('stock_status')">
                Status: {{ getStockStatusName(filterForm.stock_status) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.is_active_only == '1'" @remove="removeFilter('is_active_only')">
                Hanya Aktif
            </FilterBadge>
            <FilterBadge v-if="filterForm.in_stock_only == '1'" @remove="removeFilter('in_stock_only')">
                Stok > 0
            </FilterBadge>
        </div>

        <!-- Filter Modal Overlay -->
        <FilterModal
            :show="showFilterModal"
            title="Filter Stok"
            @close="closeModal"
            @reset="resetTempFilters"
            @apply="applyFilters"
        >
            <div class="space-y-4">
                    <div class="space-y-1">
                        <AsyncOutletDropdown
                            v-model="tempFilters.outlet_id"
                            placeholder="Semua Outlet"
                            @loaded="onOutletsLoaded"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Tipe Item
                        </label>
                        <select
                            v-model="tempFilters.item_type"
                            class="form-input w-full rounded-lg border-gray-200"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="raw_material">Bahan Baku</option>
                            <option value="variant_sku">Produk</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Kategori
                        </label>
                        <select
                            v-model="tempFilters.category_id"
                            class="form-input w-full rounded-lg border-gray-200"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Status Stok
                        </label>
                        <select
                            v-model="tempFilters.stock_status"
                            class="form-input w-full rounded-lg border-gray-200"
                        >
                            <option value="">Semua Status</option>
                            <option value="aman">Aman</option>
                            <option value="menipis">Menipis</option>
                            <option value="habis">Habis</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="tempFilters.is_active_only" true-value="1" false-value="" class="form-checkbox text-main rounded border-gray-300">
                            <span class="text-sm">Hanya Produk Aktif</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="tempFilters.in_stock_only" true-value="1" false-value="" class="form-checkbox text-main rounded border-gray-300">
                            <span class="text-sm">Hanya Stok > 0</span>
                        </label>
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
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';
import { faSliders } from '@fortawesome/free-solid-svg-icons';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';

const props = defineProps({
    filters: Object,
    categories: Array,
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet_id: props.filters?.outlet_id ?? '',
    item_type: props.filters?.item_type ?? '',
    category_id: props.filters?.category_id ?? '',
    stock_status: props.filters?.stock_status ?? '',
    is_active_only: props.filters?.is_active_only ?? '',
    in_stock_only: props.filters?.in_stock_only ?? '',
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    outlet_id: '',
    item_type: '',
    category_id: '',
    stock_status: '',
    is_active_only: '',
    in_stock_only: '',
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
    }, 500)
);

const getOutletName = (id) => {
    return loadedOutlets.value.find(o => o.id == id)?.name || id;
};

const getCategoryName = (id) => {
    return props.categories?.find(c => c.id == id)?.name || id;
};

const getStockStatusName = (status) => {
    const statuses = { aman: 'Aman', menipis: 'Menipis', habis: 'Habis' };
    return statuses[status] || status;
};

const openModal = () => {
    tempFilters.outlet_id = filterForm.outlet_id;
    tempFilters.item_type = filterForm.item_type;
    tempFilters.category_id = filterForm.category_id;
    tempFilters.stock_status = filterForm.stock_status;
    tempFilters.is_active_only = filterForm.is_active_only;
    tempFilters.in_stock_only = filterForm.in_stock_only;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.outlet_id = '';
    tempFilters.item_type = '';
    tempFilters.category_id = '';
    tempFilters.stock_status = '';
    tempFilters.is_active_only = '';
    tempFilters.in_stock_only = '';
};

const applyFilters = () => {
    filterForm.outlet_id = tempFilters.outlet_id;
    filterForm.item_type = tempFilters.item_type;
    filterForm.category_id = tempFilters.category_id;
    filterForm.stock_status = tempFilters.stock_status;
    filterForm.is_active_only = tempFilters.is_active_only;
    filterForm.in_stock_only = tempFilters.in_stock_only;
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
        outlet_id: filterForm.outlet_id !== '' ? filterForm.outlet_id : undefined,
        item_type: filterForm.item_type !== '' ? filterForm.item_type : undefined,
        category_id: filterForm.category_id !== '' ? filterForm.category_id : undefined,
        stock_status: filterForm.stock_status !== '' ? filterForm.stock_status : undefined,
        is_active_only: filterForm.is_active_only === '1' ? '1' : undefined,
        in_stock_only: filterForm.in_stock_only === '1' ? '1' : undefined,
        page: 1,
    };

    router.get(route('inventories.stocks.index'), query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
