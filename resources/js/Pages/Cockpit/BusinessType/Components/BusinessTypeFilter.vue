<template>
    <FilterBar>
        <template #left>
            <FilterSegmented
                :model-value="visibility"
                :options="visibilityOptions"
                @update:model-value="$emit('update:visibility', $event)"
            />
        </template>

        <template #search>
            <FilterSearch
                :model-value="search"
                placeholder="Cari nama / kode jenis bisnis..."
                @update:model-value="$emit('update:search', $event)"
                @clear="$emit('update:search', '')"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { computed } from 'vue'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterSegmented from '@/Components/UI/Filter/FilterSegmented.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    search: {
        type: String,
        default: '',
    },
    visibility: {
        type: String,
        default: 'all',
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

defineEmits(['update:search', 'update:visibility'])

const visibilityOptions = computed(() => [
    { value: 'all', label: 'Semua Status' },
    { value: 'visible', label: 'Tampil', count: props.visibleCount },
    { value: 'hidden', label: 'Tersembunyi', count: props.hiddenCount },
])
</script>
