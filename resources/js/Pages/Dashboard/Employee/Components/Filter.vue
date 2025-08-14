<template>
    <div class="flex flex-row gap-2">
        <div>
            <GroupTextIconField
                :icon="faSearch"
                v-model="filterForm.search"
                class="sm"
                placeholder="Cari"
            />
        </div>
        <div class="col-span-2">
            <GroupDropdownIconField
                :icon="faUser"
                v-model="filterForm.status"
                class="sm"
                :options="[
                    { value: 'active', label: 'Aktif' },
                    { value: 'deleted', label: 'Dihapus' },
                ]"
            />
        </div>
    </div>
</template>

<script setup>
import GroupDropdownIconField from "@/Components/Dashboard/Form/GroupDropdownIconField.vue";
import GroupTextIconField from "@/Components/Dashboard/Form/GroupTextIconField.vue";
import { faSearch, faUser } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { reactive, watch } from "vue";

const props = defineProps({
    filters: Object,
});

const filterForm = reactive({
    search: props.filters?.search ?? "",
    order: props.filters?.order ?? "desc",
    by: props.filters?.by ?? "created_at",
    status: props.filters?.status ?? "active",
});

watch(
    filterForm,
    debounce(
        () =>
            router.get(route("dashboard.employees.index"), filterForm, {
                preserveState: true,
                preserveScroll: true,
            }),
        500
    )
);
</script>
