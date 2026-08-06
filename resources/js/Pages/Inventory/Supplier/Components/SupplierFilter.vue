<template>
    <div class="flex flex-wrap items-center gap-2">
        <!-- Search bar -->
        <div>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama, email, alamat..."
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
            <FilterBadge v-if="filterForm.is_active !== ''" @remove="removeFilter('is_active')">
                Status: {{ filterForm.is_active == '1' ? 'Aktif' : 'Nonaktif' }}
            </FilterBadge>
        </div>

        <!-- Filter Modal Overlay -->
        <FilterModal
            :show="showFilterModal"
            title="Filter Supplier"
            @close="closeModal"
            @reset="resetTempFilters"
            @apply="applyFilters"
        >
            <div class="space-y-4">
                    <!-- Status Filter -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Status Aktif
                        </label>
                        <select
                            v-model="tempFilters.is_active"
                            class="form-input w-full rounded-lg border-gray-200"
                        >
                            <option value="">Semua</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
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
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';

const props = defineProps({
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    is_active: props.filters?.is_active ?? '',
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    is_active: '',
});

// Watch search separately for immediate query trigger
watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery();
    }, 500)
);

const openModal = () => {
    tempFilters.is_active = filterForm.is_active;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.is_active = '';
};

const applyFilters = () => {
    filterForm.is_active = tempFilters.is_active;
    showFilterModal.value = false;
    updateQuery();
};

const removeFilter = (key) => {
    if (key === 'is_active') filterForm.is_active = '';
    updateQuery();
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        is_active: filterForm.is_active !== '' ? filterForm.is_active : undefined,
        page: 1,
    };

    router.get(route('inventory.suppliers.index'), query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
