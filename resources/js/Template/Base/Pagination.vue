<template>
    <Card title="Pagination Complete" class="mb-4">
        <Pagination
            :links="users.links"
            :from="users.from"
            :to="users.to"
            :total="users.total"
            :per-page="users.per_page"
        ></Pagination>
    </Card>
    <Card title="Pagination without per page">
        <Pagination
            :links="users.links"
            :from="users.from"
            :to="users.to"
            :total="users.total"
        ></Pagination>
    </Card>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
import Pagination from "@/Components/UI/Pagination.vue";

defineProps({
    users: Object,
});
</script>
