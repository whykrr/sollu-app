<template>
    <div class="flex flex-row items-center gap-2">
        <div>
            <FilterSearch v-model="filterForm.search" />
        </div>
        <div>
            <FilterStatus v-model="filterForm.status" />
        </div>
    </div>
</template>

<script setup>
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterStatus from '@/Components/UI/Filter/FilterStatus.vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { reactive, watch } from 'vue';

const props = defineProps({
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
});

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route('products.categories.index'),
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
