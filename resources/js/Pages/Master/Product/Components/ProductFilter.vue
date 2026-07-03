<template>
    <div class="flex items-center gap-2">
        <div>
            <FilterSearch v-model="filterForm.search" />
        </div>
        <div v-if="outlets.length !== 1 && selectedOutlet === null">
            <GroupDropdownIconField
                id="outlets"
                v-model="filterForm.outlet"
                :icon="faMapMarkerAlt"
                placeholder="Semua Outlet"
                class="sm"
                :options="outlets"
            />
        </div>
        <div>
            <GroupDropdownIconField
                id="category"
                v-model="filterForm.category"
                :icon="faBox"
                placeholder="Semua Kategori"
                class="sm"
                :options="categories"
            />
        </div>
        <div v-if="hasFilter">
            <button
                class="btn btn-outline-danger btn-sm"
                title="Reset filter"
                @click="resetFilter"
            >
                <FontAwesomeIcon :icon="faClose" />
            </button>
        </div>
        <div class="grow"></div>
        <div>
            <Switch
                id="switch_regular"
                name="switch_regular"
                labeling="Tampilkan Arsip"
                size="sm"
                v-model="filterForm.is_deleted"
            />
        </div>
    </div>
</template>

<script setup>
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import Switch from '@/Components/Form/Switch.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import {
    faBox,
    faClose,
    faMapMarkerAlt,
    faUserShield,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, reactive, watch } from 'vue';

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}));

const selectedOutlet = computed(() => usePage().props.selectedOutlet);

const props = defineProps({
    filters: Object,
    categories: Array,
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ?? '',
    category: props.filters?.category ?? '',
    is_deleted: props.filters?.is_deleted ?? 0,
});

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route('master.products.index'),
                { ...route().params, ...filterForm, page: 1 },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            ),
        500,
    ),
);

const hasFilter = computed(() => {
    return (
        filterForm.search !== '' ||
        filterForm.outlet !== '' ||
        filterForm.category !== ''
    );
});

const resetFilter = () => {
    filterForm.search = '';
    filterForm.outlet = '';
    filterForm.category = '';
};
</script>
