<template>
    <div class="flex items-center gap-2">
        <FilterSearch v-model="filterForm.search" placeholder="Cari Nama Kasir" />

        <button class="btn btn-outline-main btn-sm" @click="openFilter">
            <FontAwesomeIcon :icon="faSliders" />
            Filter
        </button>

        <!-- Active Filters Display -->
        <FilterBadge 
            v-if="filterForm.status"
            label="Status"
            :value="filterForm.status"
            @remove="removeFilter('status')"
        />

        <FilterModal :show="showFilter" @close="closeFilter" @apply="applyFilter" @reset="resetFilter">
            <div class="space-y-4">
                <DropdownField
                    label="Status Shift"
                    v-model="tempFilters.status"
                    :options="statusOptions"
                    placeholder="Semua Status"
                />
            </div>
        </FilterModal>
    </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { faSliders } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const showFilter = ref(false);

const filterForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || '',
});

const tempFilters = reactive({
    status: '',
});

const statusOptions = [
    { value: 'open', label: 'Buka' },
    { value: 'closed', label: 'Tutup' },
];

const updateQuery = () => {
    const query = {
        ...route().params,
        ...filterForm,
        page: 1, // Reset to page 1 on filter
    };

    // Clean up empty params
    Object.keys(query).forEach((key) => {
        if (query[key] === '' || query[key] === null || query[key] === undefined) {
            delete query[key];
        }
    });

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Watch search with debounce
watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery();
    }, 500)
);

const openFilter = () => {
    tempFilters.status = filterForm.status;
    showFilter.value = true;
};

const closeFilter = () => {
    showFilter.value = false;
};

const applyFilter = () => {
    filterForm.status = tempFilters.status;
    updateQuery();
    closeFilter();
};

const resetFilter = () => {
    tempFilters.status = '';
    filterForm.status = '';
    updateQuery();
    closeFilter();
};

const removeFilter = (key) => {
    filterForm[key] = '';
    updateQuery();
};
</script>
