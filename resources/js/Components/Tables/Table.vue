<template>
    <div>
        <table class="table table-hovered min-w-full">
            <thead>
                <tr
                    class="text-neutral-700 select-none sticky top-0 left-0 z-auto overflow-hidden"
                >
                    <th
                        v-for="head in headers"
                        :key="head.field"
                        :class="getResponsiveClass(head.show)"
                        @click="toggleSort(head)"
                    >
                        <div
                            class="flex flex-row items-center gap-2 cursor-pointer hover:text-neutral-900 transition-colors duration-150"
                        >
                            <span>{{ head.label }}</span>
                            <div
                                v-if="head.sortable"
                                class="inline-flex items-center text-xs ml-0.5"
                            >
                                <FontAwesomeIcon
                                    v-if="sortKey !== head.field"
                                    :icon="faSort"
                                    class="text-neutral-400/50 transition-colors duration-150"
                                />
                                <FontAwesomeIcon
                                    v-else
                                    :icon="
                                        sortOrder === 'asc'
                                            ? faSortUp
                                            : faSortDown
                                    "
                                    class="text-neutral-800 transition-colors duration-150"
                                />
                            </div>
                        </div>
                    </th>
                    <th width="1%" class="sticky top-0 z-10" />
                </tr>
            </thead>
            <tbody>
                <template v-if="data.length > 0">
                    <tr
                        v-for="row in data"
                        :key="row.id"
                        @click="handleRowClick(row)"
                    >
                        <td
                            v-for="col in headers"
                            :key="col.field"
                            :class="getResponsiveClass(col.show)"
                        >
                            <slot
                                v-if="col.slot"
                                :name="col.slot"
                                :row="row"
                                :item="row"
                            />
                            <template v-else-if="row[col.field]">{{
                                row[col.field]
                            }}</template>
                            <template v-else>-</template>
                        </td>
                        <td>
                            <span class="text-sm" v-if="!action">
                                <FontAwesomeIcon :icon="faEllipsis" />
                            </span>
                            <div class="flex gap-1" v-else>
                                <slot
                                    name="actions"
                                    :row="row"
                                    :item="row"
                                ></slot>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td
                        :colspan="headers.length + 1"
                        class="text-center text-neutral-400 border-0 bg-slate-50/50 py-6"
                    >
                        data tidak ditemukan.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import {
    faEllipsis,
    faEllipsisVertical,
    faEye,
    faSort,
    faSortDown,
    faSortUp,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from '../UI/Card/Card.vue';
import TextField from '../Form/TextField.vue';

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
        default: 'asc',
    },
    action: {
        type: Boolean,
        default: false,
    },
});

function getResponsiveClass(show) {
    if (!show) return ''; // tampil di semua ukuran

    const map = {
        sm: 'hidden sm:table-cell',
        md: 'hidden md:table-cell',
        lg: 'hidden lg:table-cell',
        xl: 'hidden xl:table-cell',
    };

    return map[show] || '';
}

const emit = defineEmits(['row-click']);
const sortKey = ref(props.sort);
const sortOrder = ref(props.sortDirection);

function toggleSort(col) {
    if (!col.sortable) return;

    if (sortKey.value === col.field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = col.field;
        sortOrder.value = 'asc';
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
        },
    );
}

function handleRowClick(row) {
    emit('row-click', row);
}
</script>
