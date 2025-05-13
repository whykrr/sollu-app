<template>
    <div class="flex flex-col">
        <CardTransparent :title="$t('page.contentType')">
            <template #buttons>
                <Link
                    :href="route('admin.content-types.create')"
                    class="btn btn-main text-sm"
                >
                    <fa icon="fa-plus" />
                    {{ $t("action.create") }}
                </Link>
            </template>
            <div class="flex flex-col gap-2">
                <Table :data="types.data" />
                <Pagination
                    :links="types.links"
                    :from="types.from"
                    :to="types.to"
                    :total="types.to"
                />
            </div>
        </CardTransparent>
    </div>
</template>

<script setup>
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";
import Pagination from "@admin/Components/UI/Pagination.vue";
import Table from "@admin/Pages/ContentType/Components/Table.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
    types: Object,
});
</script>
