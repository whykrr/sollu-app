<template>
    <div class="flex items-center gap-2">
        <FilterSearch
v-model="filterForm.search"
                      placeholder="Cari pelanggan..." />

        <!-- Filter Modal Toggle -->
        <button
class="btn btn-flat btn-sm bg-white"
                @click="showFilterModal = true">
            <FontAwesomeIcon :icon="faSliders" />
            <span
                  class="hidden md:inline">Filter</span>
        </button>

        <FilterBadge
v-if="filterForm.is_active !== '' && filterForm.is_active !== null && filterForm.is_active !== undefined"
                     @remove="filterForm.is_active = ''">
            Status: {{statusOptions.find((o) =>
                o.value ==
            filterForm.is_active)?.label}}
        </FilterBadge>

        <FilterModal
:show="showFilterModal"
                     title="Filter Pelanggan"
                     @close="showFilterModal = false"
                     @apply="applyFilters"
                     @reset="resetFilters">
            <div class="space-y-4">
                <DropdownField
v-model="tempFilters.is_active"
                               label="Status"
                               :options="statusOptions" />
            </div>
        </FilterModal>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faSliders } from '@fortawesome/free-solid-svg-icons';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import { debounce } from 'lodash';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = ref({
    search: props.filters?.search || '',
    is_active: props.filters?.is_active || '',
});

const tempFilters = ref({
    is_active: filterForm.value.is_active,
});

const showFilterModal = ref(false);

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Tidak Aktif' },
];

const hasFilters = computed(() => {
    return filterForm.value.is_active !== '';
});

// Watch search input to trigger query immediately (debounced handled in Index if needed, but let's handle here)
watch(
    () => filterForm.value.search,
    debounce((newVal) => {
        updateQuery();
    }, 500)
);

watch(
    () => filterForm.value.is_active,
    () => {
        updateQuery();
    }
);

const applyFilters = () => {
    filterForm.value.is_active = tempFilters.value.is_active;
    showFilterModal.value = false;
};

const resetFilters = () => {
    filterForm.value.is_active = '';
    filterForm.value.search = '';
    tempFilters.value.is_active = '';
    showFilterModal.value = false;
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.value.search || undefined,
        is_active: filterForm.value.is_active || undefined,
        page: 1, // Reset page
    };

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
