<template>
    <div class="grid grid-cols-3 gap-2">
        <div>
            <div class="form-group">
                <label for="form_group">
                    <fa icon="fa-search" />
                </label>
                <input
                    type="text"
                    :placeholder="$t('form.search')"
                    class="form-sm"
                    v-model="filterForm.search"
                />
            </div>
        </div>
        <div class="col-span-2">
            <div class="flex flex-row gap-3 justify-end">
                <div class="form-group">
                    <label for="form_group">
                        <fa icon="fa-user" />
                    </label>
                    <select class="form-sm" v-model="filterForm.status">
                        <option value="active">
                            {{ $t("status.active") }}
                        </option>
                        <option value="deleted">
                            {{ $t("status.deleted") }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { reactive, watch } from "vue";

const props = defineProps({
    filters: Object,
});

const filterForm = reactive({
    search: props.filters.search ?? "",
    order: props.filters.order ?? "desc",
    by: props.filters.by ?? "created_at",
    status: props.filters.status ?? "active",
});

watch(
    filterForm,
    debounce(
        () =>
            router.get(route("dashboard.admin.users.index"), filterForm, {
                preserveState: true,
                preserveScroll: true,
            }),
        500
    )
);
</script>
