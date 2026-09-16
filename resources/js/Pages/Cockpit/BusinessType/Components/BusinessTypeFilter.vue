<template>
    <div
        class="bg-white p-3 rounded-xl border border-neutral-200/70 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-2"
    >
        <div class="flex flex-wrap items-center gap-2">
            <FilterSearch
                :model-value="search"
                placeholder="Cari nama atau kode jenis bisnis..."
                class="w-full sm:w-64"
                @update:model-value="$emit('update:search', $event)"
            />

            <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium">
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        visibility === 'all'
                            ? 'bg-white text-neutral-800 font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="$emit('update:visibility', 'all')"
                >
                    Semua Status
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        visibility === 'visible'
                            ? 'bg-white text-success font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="$emit('update:visibility', 'visible')"
                >
                    Tampil ({{ visibleCount }})
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        visibility === 'hidden'
                            ? 'bg-white text-neutral-600 font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="$emit('update:visibility', 'hidden')"
                >
                    Tersembunyi ({{ hiddenCount }})
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2 justify-end">
            <div class="w-48">
                <DropdownField
                    :model-value="sort"
                    :options="sortOptions"
                    @update:model-value="$emit('update:sort', $event)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'

defineProps({
    search: {
        type: String,
        default: '',
    },
    visibility: {
        type: String,
        default: 'all',
    },
    sort: {
        type: String,
        default: 'order_asc',
    },
    visibleCount: {
        type: Number,
        default: 0,
    },
    hiddenCount: {
        type: Number,
        default: 0,
    },
})

defineEmits(['update:search', 'update:visibility', 'update:sort'])

const sortOptions = [
    { value: 'order_asc', label: 'Urutan: Terendah' },
    { value: 'name_asc', label: 'Nama: (A - Z)' },
    { value: 'merchants_desc', label: 'Merchant Terbanyak' },
    { value: 'features_desc', label: 'Fitur Terbanyak' },
]
</script>
