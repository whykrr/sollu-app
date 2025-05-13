<template>
    <div class="table-responsive">
        <table class="table table-hovered">
            <thead>
                <tr>
                    <td>{{ $t("field.name") }}</td>
                    <td>Email</td>
                    <td>{{ $t("field.role") }}</td>
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
                        router.get(route('admin.users.edit', { user: d.id }))
                    "
                >
                    <td>{{ d.name }}</td>
                    <td>
                        {{ d.email }}
                    </td>
                    <td>
                        <div
                            v-if="d.role == 'superadmin'"
                            class="badge badge-main inline"
                        >
                            Super Admin
                        </div>
                        <div
                            v-if="d.role == 'admin'"
                            class="badge badge-secondary inline"
                        >
                            Admin
                        </div>
                        <div
                            v-if="d.role == 'editor'"
                            class="badge badge-green-900 inline"
                        >
                            Editor
                        </div>
                        <div
                            v-if="d.role == 'viewer'"
                            class="badge badge-gray-900 inline"
                        >
                            Viewer
                        </div>
                    </td>
                    <td>{{ d.created_at }}</td>
                    <td>{{ d.updated_at }}</td>
                </tr>
            </tbody>
            <tr v-if="data.length == 0">
                <td colspan="5" class="text-center text-gray-400">
                    No data found.
                </td>
            </tr>
        </table>
    </div>
</template>

<script setup>
import Badge from "@admin/Template/Notifications/Badge.vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    data: Array,
});
</script>
