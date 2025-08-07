<template>
    <table class="table table-hovered w-full">
        <thead class="sticky top-0 z-10 bg-white">
            <tr>
                <td>Nama</td>
                <td>Email</td>
                <td>Akses</td>
                <td>Outlet</td>
                <td>Terakhir Diubah</td>
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
                        route('dashboard.admin.users.edit', { user: d.id })
                    )
                "
            >
                <td>{{ d.name }}</td>
                <td>
                    {{ d.email }}
                </td>
                <td>
                    {{ d.roles[0].label }}
                </td>
                <td>
                    <span class="space-x-0.5" v-if="d.outlets.length > 0">
                        <label
                            v-for="outlet in d.outlets"
                            class="badge pill badge-info"
                            >{{ outlet.name }}</label
                        >
                    </span>
                    <span v-else class="text-gray-400">
                        <label class="badge pill badge-info"
                            >Semua Outlet</label
                        >
                    </span>
                </td>
                <td>{{ d.updated_at }}</td>
            </tr>
        </tbody>
        <tr v-if="data.length == 0">
            <td colspan="5" class="text-center text-gray-400">
                No data found.
            </td>
        </tr>
    </table>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    data: Array,
});
</script>
