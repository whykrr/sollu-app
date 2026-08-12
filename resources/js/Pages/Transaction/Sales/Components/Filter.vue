<template>
    <div class="flex items-center gap-2">
        <FilterSearch
            v-model="filterForm.search"
            placeholder="Cari No. Struk atau Pelanggan"
        />

        <button class="btn btn-flat btn-sm" @click="openFilter">
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
        <FilterBadge
            v-if="filterForm.channel"
            label="Channel"
            :value="filterForm.channel"
            @remove="removeFilter('channel')"
        />
        <FilterBadge
            v-if="filterForm.payment_status"
            label="Status Bayar"
            :value="filterForm.payment_status"
            @remove="removeFilter('payment_status')"
        />

        <FilterModal
            :show="showFilter"
            @close="closeFilter"
            @apply="applyFilter"
            @reset="resetFilter"
        >
            <div class="space-y-4">
                <DropdownField
                    label="Status Transaksi"
                    v-model="tempFilters.status"
                    :options="statusOptions"
                    placeholder="Semua Status"
                />
                <DropdownField
                    label="Channel Pembelian"
                    v-model="tempFilters.channel"
                    :options="channelOptions"
                    placeholder="Semua Channel"
                />
                <DropdownField
                    label="Status Pembayaran"
                    v-model="tempFilters.payment_status"
                    :options="paymentStatusOptions"
                    placeholder="Semua Status Bayar"
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
    channel: props.filters.channel || '',
    payment_status: props.filters.payment_status || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || '',
});

const tempFilters = reactive({
    status: '',
    channel: '',
    payment_status: '',
});

const statusOptions = [
    { value: 'completed', label: 'Selesai' },
    { value: 'hold', label: 'Ditahan' },
    { value: 'void', label: 'Dibatalkan' },
];

const channelOptions = [
    { value: 'pos', label: 'POS' },
    { value: 'invoice', label: 'Invoice B2B' },
];

const paymentStatusOptions = [
    { value: 'paid', label: 'Lunas' },
    { value: 'unpaid', label: 'Belum Lunas' },
];

const updateQuery = () => {
    const query = {
        ...route().params,
        ...filterForm,
        page: 1, // Reset to page 1 on filter
    };

    // Clean up empty params
    Object.keys(query).forEach((key) => {
        if (
            query[key] === '' ||
            query[key] === null ||
            query[key] === undefined
        ) {
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
    }, 500),
);

const openFilter = () => {
    tempFilters.status = filterForm.status;
    tempFilters.channel = filterForm.channel;
    tempFilters.payment_status = filterForm.payment_status;
    showFilter.value = true;
};

const closeFilter = () => {
    showFilter.value = false;
};

const applyFilter = () => {
    filterForm.status = tempFilters.status;
    filterForm.channel = tempFilters.channel;
    filterForm.payment_status = tempFilters.payment_status;
    updateQuery();
    closeFilter();
};

const resetFilter = () => {
    tempFilters.status = '';
    tempFilters.channel = '';
    tempFilters.payment_status = '';
    filterForm.status = '';
    filterForm.channel = '';
    filterForm.payment_status = '';
    updateQuery();
    closeFilter();
};

const removeFilter = (key) => {
    filterForm[key] = '';
    updateQuery();
};
</script>
