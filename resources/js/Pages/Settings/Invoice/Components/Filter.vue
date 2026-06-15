<template>
    <div class="flex flex-row items-center gap-2">
        <div>
            <div class="form-group has-text">
                <label for="filter_search" class="form-group-text">
                    <FontAwesomeIcon :icon="faSearch" />
                </label>

                <input
                    id="filter_search"
                    v-model="filterForm.search"
                    type="text"
                    class="form sm"
                    placeholder="Cari Kode ..."
                />
            </div>
        </div>

        <div>
            <select
                id="filter_status"
                v-model="filterForm.status"
                class="form pr-10 sm"
            >
                <option value="">Semua Status</option>
                <option
                    v-for="(option, index) in optionsStatus"
                    :key="index"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>
    </div>
</template>

<script setup>
import { faSearch } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

const optionsStatus = [
    { value: 'unpaid', label: 'Menunggu Pembayaran' },
    { value: 'paid', label: 'Terbayar' },
    { value: 'canceled', label: 'Dibatalkan' },
    { value: 'expired', label: 'Kadaluarsa' },
];

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route('merchant.invoices.index'),
                { ...route().params, ...filterForm, page: 1 },
                {
                    preserveState: true,
                    preserveScroll: true,
                }
            ),
        500
    )
);
</script>
