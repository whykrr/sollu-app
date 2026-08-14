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
                    v-model="tempFilters.status"
                    label="Status Transaksi / Invoice"
                    :options="statusOptions"
                    placeholder="Semua Status"
                />
                <DropdownField
                    v-model="tempFilters.channel"
                    label="Channel Penjualan"
                    :options="channelOptions"
                    placeholder="Semua Channel"
                />
                <div class="grid grid-cols-2 gap-4">
                    <TextField
                        v-model="tempFilters.start_date"
                        type="date"
                        label="Dari Tanggal"
                    />
                    <TextField
                        v-model="tempFilters.end_date"
                        type="date"
                        label="Sampai Tanggal"
                    />
                </div>
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
import TextField from '@/Components/Form/TextField.vue';

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
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || '',
});

const tempFilters = reactive({
    status: '',
    channel: '',
    start_date: '',
    end_date: '',
});

const statusOptions = [
    { value: 'draft', label: 'Draf' },
    { value: 'unpaid', label: 'Belum Lunas' },
    { value: 'paid', label: 'Lunas' },
    { value: 'cancel', label: 'Dibatalkan' },
];

const channelOptions = [
    { value: 'e_commerce', label: 'E-Commerce' },
    { value: 'social_media', label: 'Social Media' },
    { value: 'direct', label: 'Direct / B2B' },
    { value: 'wholesale', label: 'Wholesale' },
    { value: 'custom', label: 'Custom' },
    { value: 'dine_in', label: 'POS - Dine In' },
    { value: 'take_away', label: 'POS - Take Away' },
    { value: 'online_delivery', label: 'POS - Online Delivery' },
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
    tempFilters.start_date = filterForm.start_date;
    tempFilters.end_date = filterForm.end_date;
    showFilter.value = true;
};

const closeFilter = () => {
    showFilter.value = false;
};

const applyFilter = () => {
    filterForm.status = tempFilters.status;
    filterForm.channel = tempFilters.channel;
    filterForm.start_date = tempFilters.start_date;
    filterForm.end_date = tempFilters.end_date;
    updateQuery();
    closeFilter();
};

const resetFilter = () => {
    tempFilters.status = '';
    tempFilters.channel = '';
    tempFilters.start_date = '';
    tempFilters.end_date = '';
    filterForm.status = '';
    filterForm.channel = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    updateQuery();
    closeFilter();
};

const removeFilter = (key) => {
    filterForm[key] = '';
    updateQuery();
};
</script>
