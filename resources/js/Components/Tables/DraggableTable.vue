<template>
    <div class="overflow-x-auto table-responsive">
        <table class="table table-hovered min-w-full">
            <thead>
                <tr
                    class="text-neutral-700 select-none sticky top-0 left-0 z-auto overflow-hidden"
                >
                    <th width="40px" class="text-center font-medium sticky top-0 z-10"></th>
                    <th
                        v-for="head in headers"
                        :key="head.field"
                        :class="getResponsiveClass(head.show)"
                        @click="toggleSort(head)"
                    >
                        <div
                            class="flex flex-row items-center gap-2"
                            :class="{ 'cursor-pointer hover:text-neutral-900 transition-colors duration-150': head.sortable }"
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
                    <th v-if="action" width="1%" class="sticky top-0 z-10 text-right" />
                </tr>
            </thead>
            <draggable
                v-if="list.length > 0"
                v-model="list"
                tag="tbody"
                :item-key="itemKey"
                :handle="handle"
                :disabled="disabled"
                @end="onDragEnd"
                @change="onChange"
            >
                <template #item="{ element: row }">
                    <tr
                        :key="row[itemKey]"
                        class="group hover:bg-neutral-50 transition-colors"
                        @click="handleRowClick(row)"
                    >
                        <td class="text-center py-3 select-none w-10">
                            <slot name="drag-handle" :row="row" :item="row">
                                <FontAwesomeIcon
                                    :icon="faGripVertical"
                                    class="text-neutral-300 cursor-grab active:cursor-grabbing hover:text-neutral-600 drag-handle opacity-50 group-hover:opacity-100 transition-opacity"
                                />
                            </slot>
                        </td>
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
                        <td v-if="action" class="text-right">
                            <div class="flex gap-1 justify-end">
                                <slot
                                    name="actions"
                                    :row="row"
                                    :item="row"
                                ></slot>
                            </div>
                        </td>
                    </tr>
                </template>
            </draggable>
            <tbody v-else>
                <tr>
                    <td
                        :colspan="headers.length + (action ? 2 : 1)"
                        class="text-center text-neutral-400 border-0 bg-slate-50/50 py-6"
                    >
                        Data tidak ditemukan.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faGripVertical,
    faSort,
    faSortDown,
    faSortUp,
} from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/vue3';

defineOptions({
    name: 'DraggableTable',
});

const props = defineProps({
    modelValue: {
        type: Array,
        required: true,
        default: () => [],
    },
    headers: {
        type: Array,
        required: true,
    },
    itemKey: {
        type: String,
        default: 'id',
    },
    handle: {
        type: String,
        default: '.drag-handle',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    action: {
        type: Boolean,
        default: false,
    },
    sort: {
        type: String,
        default: null,
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
});

const emit = defineEmits([
    'update:modelValue',
    'drag-end',
    'change',
    'row-click',
]);

const list = computed({
    get: () => props.modelValue,
    set: (value) => {
        emit('update:modelValue', value);
    },
});

const sortKey = ref(props.sort);
const sortOrder = ref(props.sortDirection);

watch(
    () => props.sort,
    (val) => {
        sortKey.value = val;
    },
);

watch(
    () => props.sortDirection,
    (val) => {
        sortOrder.value = val;
    },
);

function getResponsiveClass(show) {
    if (!show) return '';

    const map = {
        sm: 'hidden sm:table-cell',
        md: 'hidden md:table-cell',
        lg: 'hidden lg:table-cell',
        xl: 'hidden xl:table-cell',
    };

    return map[show] || '';
}

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

function onDragEnd(event) {
    const orderedIds = list.value.map((item) => item[props.itemKey]);
    emit('drag-end', {
        orderedIds,
        list: list.value,
        event,
    });
}

function onChange(event) {
    emit('change', event);
}

function handleRowClick(row) {
    emit('row-click', row);
}
</script>
