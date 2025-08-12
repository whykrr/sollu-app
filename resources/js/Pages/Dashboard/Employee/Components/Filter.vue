<template>
    <div class="flex flex-row gap-2">
        <div>
            <div class="form-group">
                <label for="form_group">
                    <FontAwesomeIcon :icon="faSearch" />
                </label>
                <input
                    type="text"
                    placeholder="Cari"
                    class="form-sm"
                    v-model="filterForm.search"
                />
            </div>
        </div>
        <div class="col-span-2">
            <div class="flex flex-row gap-3 justify-end">
                <div class="form-group">
                    <label for="form_group">
                        <FontAwesomeIcon :icon="faUser" />
                    </label>
                    <select class="form-sm" v-model="filterForm.status">
                        <option value="active">Aktif</option>
                        <option value="deleted">Dihapus</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
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
