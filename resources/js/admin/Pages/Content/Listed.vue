<template>
    <CardTransparent :title="'Section ' + contentType.name">
        <template #buttons>
            <Link
                :href="
                    route('admin.contents.create', {
                        content_type: contentType.id,
                    })
                "
                class="btn btn-main btn-sm"
            >
                <fa icon="fa-plus" />
                {{ $t("action.create") }}
            </Link>
        </template>
        <div class="table-responsive">
            <table class="table table-hovered">
                <thead>
                    <tr>
                        <td>
                            {{ contentType.title_aliases ?? $t("field.title") }}
                        </td>
                        <td>{{ $t("table.created") }}</td>
                        <td>{{ $t("table.lastUpdate") }}</td>
                    </tr>
                </thead>
                <tbody>
                    <tr class="h-2"></tr>
                    <tr
                        v-for="d in contentType.contents"
                        class="text-nowrap"
                        :key="d.id"
                        @click="
                            router.get(
                                route('admin.contents.edit', {
                                    content_type: d.content_type_id,
                                    id: d.id,
                                })
                            )
                        "
                    >
                        <td>{{ d.title }}</td>
                        <td>{{ d.created_at }}</td>
                        <td>{{ d.updated_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </CardTransparent>
</template>
<script setup>
import CardTransparent from "@admin/Components/UI/CardTransparent.vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
    contentType: Object,
});
</script>
