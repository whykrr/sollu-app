<template>
    <div>
        <table class="table table-hovered min-w-full">
            <thead class="">
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
                                            sortKey === head.field &&
                                            sortOrder === 'asc',
                                    }"
                                />
                                <FontAwesomeIcon
                                    :icon="faSortDown"
                                    class="absolute"
                                    :class="{
                                        'text-neutral-700':
                                            sortKey === head.field &&
                                            sortOrder === 'desc',
                                    }"
                                />
                            </div>
                        </div>
                    </th>
                    <th width="1%" class="sticky top-0 z-10" />
                </tr>
            </thead>
            <tbody>
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
                        <slot v-if="col.slot" :name="col.slot" :row="row" />
                        <template v-else>{{ row[col.field] }}</template>
                    </td>
                    <td>
                        <span class="text-sm" v-if="!action">
                            <FontAwesomeIcon :icon="faEllipsis" />
                        </span>
                        <div class="flex gap-1" v-else>
                            <slot name="actions" :row="row"></slot>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td
                        v-if="data.length === 0"
                        :colspan="headers.length + 1"
                        class="text-center text-gray-400 border-0 bg-slate-50"
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
import { head } from 'lodash';
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
