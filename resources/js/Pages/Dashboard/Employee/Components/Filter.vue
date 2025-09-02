<template>
    <div class="flex flex-row items-center gap-2">
        <div>
            <GroupTextIconField
                :icon="faSearch"
                v-model="filterForm.search"
                class="sm"
                id="search"
                placeholder="Cari"
            />
        </div>
        <div class="">
            <GroupDropdownIconField
                :icon="faMapMarkerAlt"
                placeholder="Semua Outlet"
                v-model="filterForm.outlet"
                class="sm"
                id="outlets"
                :options="outlets"
            />
        </div>
        <div class="">
            <GroupDropdownIconField
                :icon="faUserShield"
                placeholder="Semua Peran"
                v-model="filterForm.role"
                class="sm"
                id="roles"
                :options="roles"
            />
        </div>
        <div>
            <GroupDropdownIconField
                :icon="faDatabase"
                placeholder="Aktif"
                v-model="filterForm.status"
                class="sm"
                id="status"
                :options="[
                    { value: 'archived', label: 'Arsip' },
                    { value: 'all', label: 'Semua' },
                ]"
            />
        </div>
    </div>
</template>

<script setup>
import GroupDropdownIconField from "@/Components/Dashboard/Form/GroupDropdownIconField.vue";
import GroupTextIconField from "@/Components/Dashboard/Form/GroupTextIconField.vue";
import {
    faDatabase,
    faMapMarkerAlt,
    faSearch,
    faUserShield,
} from "@fortawesome/free-solid-svg-icons";
import { router, usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { reactive, watch } from "vue";

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}));

const props = defineProps({
    filters: Object,
    roles: Object,
});

const filterForm = reactive({
    search: props.filters?.search ?? "",
    outlet: props.filters?.outlet ?? "",
    role: props.filters?.role ?? "",
    status: props.filters?.status ?? "",
});

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route("dashboard.employees.index"),
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
