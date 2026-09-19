<template>
    <ActionBar>
        <template #filters>
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

        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Jenis Bisnis</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
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

defineEmits(['update:search', 'update:visibility', 'create'])

const visibilityOptions = computed(() => [
    { value: 'all', label: 'Semua Status' },
    { value: 'visible', label: 'Tampil', count: props.visibleCount },
    { value: 'hidden', label: 'Tersembunyi', count: props.hiddenCount },
])
</script>
