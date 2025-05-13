<template>
    <div class="flex flex-row gap-3">
        <div class="form-group">
            <label for="form_group">
                <fa icon="fa-envelope" />
            </label>
            <select class="form-sm" v-model="filterForm.status">
                <option value="">{{ $t("status.all") }}</option>
                <option value="unread">{{ $t("status.unread") }}</option>
                <option value="read">{{ $t("status.read") }}</option>
                <option value="replied">{{ $t("status.replied") }}</option>
            </select>
        </div>
        <div class="form-group">
            <label for="form_group">
                <fa icon="fa-search" />
            </label>
            <input
                type="text"
                :placeholder="$t('form.search')"
                class="form-sm"
                size="30"
                v-model="filterForm.search"
            />
        </div>
        <div class="grow"></div>
        <div class="form-group text-sm">
            <label for="filter_from">{{ $t("filter.from") }}</label>
            <input
                type="date"
                class="form-sm"
                id="filter_from"
                v-model="dateRange.start"
            />
            <label for="filter_to">{{ $t("filter.to") }}</label>
            <input
                type="date"
                class="form-sm"
                id="filter_to"
                :min="dateRange.start"
                :disabled="dateRange.start === null"
                v-model="dateRange.end"
            />
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";
import { debounce, filter } from "lodash";
import { reactive, watch } from "vue";

const props = defineProps({
    filters: Object,
});

const dateRange = reactive({
    start: props.filters.from ?? null,
    end: props.filters.to ?? null,
});

const filterForm = reactive({
    status: props.filters.status ?? "",
    search: props.filters.search ?? "",
    from: props.filters.from ?? null,
    to: props.filters.to ?? null,
});

watch(dateRange, () => {
    if (dateRange.start !== null && dateRange.end !== null) {
        filterForm.from = dateRange.start;
        filterForm.to = dateRange.end;
    }
});

watch(
    filterForm,
    debounce(
        () =>
            router.get(route("admin.message.index"), filterForm, {
                preserveState: true,
                preserveScroll: true,
            }),
        500
    )
);
</script>
