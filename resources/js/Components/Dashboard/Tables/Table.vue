<template>
    <table class="table table-hovered min-w-full">
        <thead class="">
            <tr
                class="text-neutral-700 select-none sticky top-0 left-0 z-auto overflow-hidden"
            >
                <th
                    v-for="head in headers"
                    @click="toggleSort(head)"
                    :key="head.key"
                >
                    <div
                        class="flex flex-row items-center gap-3 cursor-pointer hover:!text-neutral-800"
                    >
                        <span>{{ head.label }}</span>
                        <div
                            v-if="head.sortable"
                            class="flex flex-col relative text-neutral-400/50"
                        >
                            <FontAwesomeIcon
                                :icon="faSortUp"
                                :class="{
                                    'text-neutral-700':
                                        sortKey === head.key &&
                                        sortOrder === 'asc',
                                }"
                            />
                            <FontAwesomeIcon
                                :icon="faSortDown"
                                class="absolute"
                                :class="{
                                    'text-neutral-700':
                                        sortKey === head.key &&
                                        sortOrder === 'desc',
                                }"
                            />
                            <!-- <FontAwesomeIcon
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
                            /> -->
                        </div>
                    </div>
                </th>
                <th width="1%" class="sticky top-0 z-10"></th>
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
                    <span class="text-sm">
                        <FontAwesomeIcon :icon="faEllipsisVertical" />
                    </span>
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
    sort: {
        type: String,
        default: null,
    },
    sortDirection: {
        type: String,
        default: "asc",
    },
});

const emit = defineEmits(["row-click"]);
const sortKey = ref(props.sort);
const sortOrder = ref(props.sortDirection);

function toggleSort(col) {
    if (!col.sortable) return;

    if (sortKey.value === col.key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = col.key;
        sortOrder.value = "asc";
    }

    router.get(
        window.location.pathname,
        {
            ...route().params,
            page: 1,
            sort: sortKey.value,
            direction: sortOrder.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
}

function handleRowClick(row) {
    emit("row-click", row);
}
</script>
