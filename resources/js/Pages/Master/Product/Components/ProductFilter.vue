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
                class="btn btn-sm border border-gray-200 hover:border-gray-300 bg-white"
                @click="openModal"
            >
                <span>Filter</span>
                <FontAwesomeIcon :icon="faSliders" />
            </button>
        </div>

        <!-- Active Filter Badges -->
        <div class="flex-1 flex flex-wrap items-center gap-1.5">
            <FilterBadge v-if="filterForm.category" @remove="removeFilter('category')">
                Kategori: {{ getCategoryLabel(filterForm.category) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.outlet" @remove="removeFilter('outlet')">
                Outlet: {{ getOutletLabel(filterForm.outlet) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.is_deleted" @remove="removeFilter('is_deleted')">
                Tampilkan Arsip
            </FilterBadge>
        </div>

        <!-- Filter Modal Overlay -->
        <FilterModal
            :show="showFilterModal"
            title="Filter Produk"
            @close="closeModal"
            @reset="resetTempFilters"
            @apply="applyFilters"
        >
            <!-- Body -->
            <div class="space-y-4">
                    <!-- Category Filter -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >Kategori</label
                        >
                        <GroupDropdownIconField
                            id="category"
                            v-slot="selectProps"
                            v-model="tempFilters.category"
                            :icon="faBox"
                            placeholder="Semua Kategori"
                            class="w-full"
                            :options="categories"
                        />
                    </div>

                    <!-- Outlet Filter -->
                    <div
                        v-if="outlets.length > 1 && selectedOutlet === null"
                        class="space-y-1"
                    >
                        <label
                            class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >Outlet</label
                        >
                        <div class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2">
                            <SelectionGroupField
                                id="outlets"
                                v-model="tempFilters.outlet"
                                name="outlet"
                                class="sm btn-sm"
                                :options="outlets"
                            />
                        </div>
                    </div>

                    <!-- Show Archived Filter -->
                    <div
                        class="flex items-center justify-between border-t pt-3"
                    >
                        <span class="text-sm font-medium text-slate-700"
                            >Tampilkan Arsip</span
                        >
                        <Switch
                            id="switch_regular"
                            v-model="tempFilters.is_deleted"
                            name="switch_regular"
                            size="sm"
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
import {
    faBox,
    faMapMarkerAlt,
    faSliders,
} from '@fortawesome/free-solid-svg-icons';
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';
import Switch from '@/Components/Form/Switch.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';

const props = defineProps({
    filters: Object,
    categories: Array,
});

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}));

const selectedOutlet = computed(() => usePage().props.selectedOutlet);

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ?? '',
    category: props.filters?.category ?? '',
    is_deleted: props.filters?.is_deleted ? true : false,
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    category: '',
    outlet: '',
    is_deleted: false,
});

// Watch search separately for immediate query trigger
watch(
    () => filterForm.search,
    debounce((newVal) => {
        updateQuery();
    }, 500),
);

const getCategoryLabel = (catId) => {
    return props.categories.find((c) => c.value === catId)?.label ?? catId;
};

const getOutletLabel = (outId) => {
    return outlets.find((o) => o.value === outId)?.label ?? outId;
};

const openModal = () => {
    tempFilters.category = filterForm.category;
    tempFilters.outlet = filterForm.outlet;
    tempFilters.is_deleted = filterForm.is_deleted;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.category = '';
    tempFilters.outlet = '';
    tempFilters.is_deleted = false;
};

const applyFilters = () => {
    filterForm.category = tempFilters.category;
    filterForm.outlet = tempFilters.outlet;
    filterForm.is_deleted = tempFilters.is_deleted;
    showFilterModal.value = false;
    updateQuery();
};

const removeFilter = (key) => {
    if (key === 'category') filterForm.category = '';
    if (key === 'outlet') filterForm.outlet = '';
    if (key === 'is_deleted') filterForm.is_deleted = false;
    updateQuery();
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        category: filterForm.category || undefined,
        outlet: filterForm.outlet || undefined,
        is_deleted: filterForm.is_deleted ? 1 : undefined,
        page: 1,
    };

    router.get(route('master.products.index'), query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
