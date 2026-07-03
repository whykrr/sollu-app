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
            <!-- Category Badge -->
            <div v-if="filterForm.category" class="filter-badge">
                <span
                    >Kategori: {{ getCategoryLabel(filterForm.category) }}</span
                >
                <button
                    type="button"
                    @click="removeFilter('category')"
                    class="filter-badge-remove"
                    title="Hapus filter kategori"
                >
                    ✕
                </button>
            </div>

            <!-- Outlet Badge -->
            <div v-if="filterForm.outlet" class="filter-badge">
                <span>Outlet: {{ getOutletLabel(filterForm.outlet) }}</span>
                <button
                    type="button"
                    @click="removeFilter('outlet')"
                    class="filter-badge-remove"
                    title="Hapus filter outlet"
                >
                    ✕
                </button>
            </div>

            <!-- Is Deleted Badge -->
            <div v-if="filterForm.is_deleted" class="filter-badge">
                <span>Tampilkan Arsip</span>
                <button
                    type="button"
                    @click="removeFilter('is_deleted')"
                    class="filter-badge-remove"
                    title="Sembunyikan Arsip"
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
                    <h3 class="overlay-title">Filter Produk</h3>
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
                        <GroupDropdownIconField
                            id="outlets"
                            v-model="tempFilters.outlet"
                            :icon="faMapMarkerAlt"
                            placeholder="Semua Outlet"
                            class="w-full"
                            :options="outlets"
                        />
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
                            name="switch_regular"
                            size="sm"
                            v-model="tempFilters.is_deleted"
                        />
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
import Switch from '@/Components/Form/Switch.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';

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
