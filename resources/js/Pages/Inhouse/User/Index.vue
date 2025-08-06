<template>
    <div class="flex flex-col">
        <CardTransparent :title="$t('sidebar.users')">
            <template #buttons>
                <Link
                    :href="route('dashboard.admin.users.create')"
                    class="btn btn-main text-sm"
                >
                    <fa icon="fa-plus" />
                    {{ $t("action.create") }}
                </Link>
            </template>
            <div class="flex flex-col gap-2">
                <Filter :filters="filters" />
                <Table :data="users.data" />
                <Pagination
                    :links="users.links"
                    :from="users.from"
                    :to="users.to"
                    :total="users.to"
                />
            </div>
        </CardTransparent>
    </div>
</template>

<script setup>
import CardTransparent from "@/Components/Cards/CardTransparent.vue";
import Pagination from "@/Components/Tables/Pagination.vue";
import Table from "@/Pages/User/Components/Table.vue";
import Filter from "@/Pages/User/Components/Filter.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
    users: Object,
    filters: Object,
});
</script>
