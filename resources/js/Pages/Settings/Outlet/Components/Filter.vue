<template>
    <div class="flex items-center gap-2">
        <div>
            <FilterSearch v-model="filterForm.search" />
        </div>
        <div class="grow"></div>
    </div>
</template>

<script setup>
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterTrashData from '@/Components/UI/Filter/FilterTrashData.vue';
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
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Tidak Aktif' },
];

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route('settings.outlets.index'),
                { ...route().params, ...filterForm, page: 1 },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            ),
        500,
    ),
);
</script>
