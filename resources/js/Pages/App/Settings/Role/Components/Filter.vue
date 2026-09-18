<template>
    <FilterBar>
        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari peran..."
                @clear="updateQuery"
            />
        </template>
    </FilterBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import FilterBar from '@/Components/UI/Filter/FilterBar.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
})

const updateQuery = () => {
    router.get(
        route('settings.roles.index'),
        { ...route().params, search: filterForm.search || undefined, page: 1 },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)
</script>
