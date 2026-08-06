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
        <div class="flex flex-wrap items-center gap-1.5">
            <FilterBadge v-if="filterForm.role" @remove="removeFilter('role')">
                Peran: {{ getRoleLabel(filterForm.role) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.outlet" @remove="removeFilter('outlet')">
                Outlet: {{ getOutletLabel(filterForm.outlet) }}
            </FilterBadge>
            <FilterBadge v-if="filterForm.is_deleted" @remove="removeFilter('is_deleted')">
                Tampilkan Arsip
            </FilterBadge>
        </div>

        <!-- Filter Modal Overlay -->
        <FilterModal
            :show="showFilterModal"
            title="Filter Pegawai"
            @close="closeModal"
            @reset="resetTempFilters"
            @apply="applyFilters"
        >
            <!-- Body -->
            <div class="space-y-4">
                    <!-- Role Filter -->
                    <div class="space-y-1">
                        <label
                            class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
                            >Peran</label
                        >
                        <GroupDropdownIconField
                            id="roles"
                            v-model="tempFilters.role"
                            :icon="faUserShield"
                            placeholder="Semua Peran"
                            class="w-full"
                            :options="roles"
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
        </FilterModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faUserShield,
    faMapMarkerAlt,
    faSliders,
} from '@fortawesome/free-solid-svg-icons';
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import Switch from '@/Components/Form/Switch.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import FilterModal from '@/Components/UI/Filter/FilterModal.vue';
import FilterBadge from '@/Components/UI/Filter/FilterBadge.vue';

const props = defineProps({
    filters: Object,
    roles: Array,
});

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}));

const selectedOutlet = computed(() => usePage().props.selectedOutlet);

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ?? '',
    role: props.filters?.role ?? '',
    is_deleted: props.filters?.is_deleted ? true : false,
});

// Modal State
const showFilterModal = ref(false);
const tempFilters = reactive({
    role: '',
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

const getRoleLabel = (roleVal) => {
    return props.roles.find((r) => r.value === roleVal)?.label ?? roleVal;
};

const getOutletLabel = (outId) => {
    return outlets.find((o) => o.value === outId)?.label ?? outId;
};

const openModal = () => {
    tempFilters.role = filterForm.role;
    tempFilters.outlet = filterForm.outlet;
    tempFilters.is_deleted = filterForm.is_deleted;
    showFilterModal.value = true;
};

const closeModal = () => {
    showFilterModal.value = false;
};

const resetTempFilters = () => {
    tempFilters.role = '';
    tempFilters.outlet = '';
    tempFilters.is_deleted = false;
};

const applyFilters = () => {
    filterForm.role = tempFilters.role;
    filterForm.outlet = tempFilters.outlet;
    filterForm.is_deleted = tempFilters.is_deleted;
    showFilterModal.value = false;
    updateQuery();
};

const removeFilter = (key) => {
    if (key === 'role') filterForm.role = '';
    if (key === 'outlet') filterForm.outlet = '';
    if (key === 'is_deleted') filterForm.is_deleted = false;
    updateQuery();
};

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        role: filterForm.role || undefined,
        outlet: filterForm.outlet || undefined,
        is_deleted: filterForm.is_deleted ? 1 : undefined,
        page: 1,
    };

    router.get(route('employees.index'), query, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
