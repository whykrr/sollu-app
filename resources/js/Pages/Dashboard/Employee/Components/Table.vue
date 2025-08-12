<template>
    <table class="table table-hovered w-full text-sm">
        <thead class="sticky top-0 z-10 bg-white">
            <tr class="text-neutral-600">
                <td
                    class="flex flex-row items-center justify-between cursor-pointer hover:text-neutral-800"
                >
                    <span>Nama</span>
                    <div class="">
                        <FontAwesomeIcon :icon="faSort" />
                    </div>
                </td>
                <td>Email</td>
                <td>Akses</td>
                <td>Outlet</td>
                <td>Terakhir Diubah</td>
                <td width="1%"></td>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="d in data"
                class="text-nowrap"
                :key="d.id"
                @click="
                    router.get(
                        route('dashboard.employees.show', { employee: d.id })
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
                            class="badge pill text-sm badge-info"
                            >{{ outlet.name }}</label
                        >
                    </span>
                    <span v-else class="text-gray-400">
                        <label class="badge pill text-sm badge-success"
                            >Semua Outlet</label
                        >
                    </span>
                </td>
                <td>{{ d.updated_at }}</td>
                <td>
                    <button
                        class="btn btn-highlight-info btn-sm"
                        title="Lihat Detail"
                    >
                        <FontAwesomeIcon :icon="faEllipsisVertical" />
                    </button>
                </td>
            </tr>
            <tr>
                <td colspan="5" class="text-center text-gray-400">
                    No data found.
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup>
import { faEllipsisVertical, faSort } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    data: Array,
});
</script>
