<template>
    <div class="overflow-auto floating-scroll h-full flex-1 p-0.5">
        <template v-if="data && data.length > 0">
            <div :class="gridClass">
                <div
                    v-for="(row, index) in data"
                    :key="row[keyField] ?? index"
                    class="h-full"
                    @click="handleCardClick(row)"
                >
                    <slot :row="row" :item="row" :index="index">
                        <slot name="item" :row="row" :item="row" :index="index">
                            <!-- Default generic card fallback if no slot is provided -->
                            <div
                                class="bg-white border border-slate-200 rounded-xl p-3 h-full flex flex-col justify-between hover:border-primary-400 hover:bg-slate-50/50 transition-all duration-150 cursor-pointer"
                            >
                                <div class="space-y-1">
                                    <div class="font-semibold text-neutral-800 text-sm">
                                        {{ row.name || row.title || row.code || '-' }}
                                    </div>
                                    <div
                                        v-if="row.description"
                                        class="text-xs text-neutral-500 line-clamp-2"
                                    >
                                        {{ row.description }}
                                    </div>
                                </div>
                            </div>
                        </slot>
                    </slot>
                </div>
            </div>
        </template>
        <template v-else>
            <slot name="empty">
                <div
                    class="py-12 px-4 text-center text-neutral-400 text-sm bg-slate-50/50 rounded-xl border border-dashed border-slate-200"
                >
                    {{ emptyMessage }}
                </div>
            </slot>
        </template>
    </div>
</template>

<script setup>
defineProps({
    data: {
        type: Array,
        required: true,
        default: () => [],
    },
    gridClass: {
        type: String,
        default: 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5',
    },
    keyField: {
        type: String,
        default: 'id',
    },
    emptyMessage: {
        type: String,
        default: 'data tidak ditemukan.',
    },
})

const emit = defineEmits(['row-click', 'item-click'])

const handleCardClick = row => {
    emit('row-click', row)
    emit('item-click', row)
}
</script>
