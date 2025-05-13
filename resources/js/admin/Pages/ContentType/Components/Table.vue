<template>
    <div class="table-responsive">
        <table class="table table-hovered">
            <thead>
                <tr>
                    <td>{{ $t("field.name") }}</td>
                    <td>{{ $t("field.description") }}</td>
                    <td>{{ $t("table.created") }}</td>
                    <td>{{ $t("table.lastUpdate") }}</td>
                </tr>
            </thead>
            <tbody>
                <tr class="h-2"></tr>
                <tr
                    v-for="d in data"
                    class="text-nowrap"
                    :key="d.id"
                    @click="
                        router.get(
                            route('admin.content-types.show', {
                                content_type: d.id,
                            })
                        )
                    "
                >
                    <td>{{ d.name }}</td>
                    <td>
                        {{ d.description }}
                    </td>
                    <td>{{ d.created_at }}</td>
                    <td>{{ d.updated_at }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    data: Array,
});
</script>
