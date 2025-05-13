<template>
    <div>
        <CardTransparent title="Inbox">
            <div class="flex flex-col gap-2">
                <Filter :filters></Filter>
                <Table :messages="messages.data"></Table>
                <Pagination
                    :links="messages.links"
                    :from="messages.from"
                    :to="messages.to"
                    :total="messages.to"
                />
            </div>
        </CardTransparent>
    </div>
</template>

<script setup>
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";
import Pagination from "@admin/Components/UI/Pagination.vue";
import Table from "@admin/Pages/Message/Components/Table.vue";
import Filter from "./Components/Filter.vue";

defineProps({
    messages: Object,
    filters: Object,
});
</script>
