<template>
    <table class="table table-hovered w-full">
        <thead class="sticky top-0 z-5 bg-white">
            <tr class="text-neutral-600">
                <td
                    v-for="head in headers"
                    @click="toggleSort(head)"
                    :key="head.key"
                    class="select-none"
                >
                    <div
                        class="flex flex-row items-center justify-between cursor-pointer hover:!text-neutral-800"
                    >
                        <span>{{ head.label }}</span>
                        <div v-if="head.sortable">
                            <FontAwesomeIcon
                                :icon="
                                    sortKey === head.key
                                        ? sortOrder === 'asc'
                                            ? faSortUp
                                            : faSortDown
                                        : faSort
                                "
                                :class="{
                                    'text-neutral-400/50 ':
                                        sortKey !== head.key,
                                }"
                            />
                        </div>
                    </div>
                </td>
                <td width="1%"></td>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="row in data"
                class="text-nowrap"
                :key="row.id"
                @click="handleRowClick(row)"
            >
                <td v-for="col in headers" :key="col.key">
                    <slot v-if="col.slot" :name="col.slot" :row="row" />
                    <template v-else>{{ row[col.key] }}</template>
                </td>
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
                <td
                    v-if="data.length === 0"
                    :colspan="headers.length + 1"
                    class="text-center text-gray-400"
                >
                    No data found.
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script setup>
import {
    faEllipsisVertical,
    faSort,
    faSortDown,
    faSortUp,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { router } from "@inertiajs/vue3";
import { head } from "lodash";
import { ref } from "vue";

const props = defineProps({
    headers: {
        type: Array,
        required: true,
    },
    data: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(["update:sort", "row-click"]);
const sortKey = ref(null);
const sortOrder = ref("asc");

function toggleSort(col) {
    if (!col.sortable) return;

    if (sortKey.value === col.key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = col.key;
        sortOrder.value = "asc";
    }

    emit("update:sort", { key: sortKey.value, order: sortOrder.value });
}

function handleRowClick(row) {
    emit("row-click", row);
}
</script>
