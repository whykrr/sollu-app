<template>
    <FilterBar>
        <template #left>
            <!-- Status Filter Segmented -->
            <FilterSegmented
                :model-value="status"
                :options="statusOptions"
                @update:model-value="$emit('update:status', $event)"
            />

            <!-- Visibility Filter Segmented -->
            <FilterSegmented
                :model-value="visibility"
                :options="visibilityOptions"
                @update:model-value="$emit('update:visibility', $event)"
            />
        </template>

        <template #search>
            <FilterSearch
                :model-value="search"
                placeholder="Cari nama atau kode paket..."
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
    status: {
        type: String,
        default: 'all',
    },
    visibility: {
        type: String,
        default: 'all',
    },
    plansCount: {
        type: Number,
        default: 0,
    },
    activeCount: {
        type: Number,
        default: 0,
    },
    inactiveCount: {
        type: Number,
        default: 0,
    },
    publicCount: {
        type: Number,
        default: 0,
    },
    hiddenCount: {
        type: Number,
        default: 0,
    },
    customCount: {
        type: Number,
        default: 0,
    },
})

defineEmits(['update:search', 'update:status', 'update:visibility'])

const statusOptions = computed(() => [
    { value: 'all', label: 'Semua' },
    { value: 'active', label: 'Aktif', count: props.activeCount },
    { value: 'inactive', label: 'Nonaktif', count: props.inactiveCount },
])

const visibilityOptions = computed(() => [
    { value: 'all', label: 'Semua Katalog' },
    { value: 'public', label: 'Publik', count: props.publicCount },
    { value: 'hidden', label: 'Tersembunyi', count: props.hiddenCount },
    { value: 'custom', label: 'Custom', count: props.customCount },
])
</script>
