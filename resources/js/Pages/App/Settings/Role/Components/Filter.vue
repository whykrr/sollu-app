<template>
    <div class="flex items-center gap-2 mt-2">
        <FilterSearch v-model="filterForm.search" placeholder="Cari peran..." />
        <div class="grow"></div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
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

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route('settings.roles.index'),
                { ...route().params, search: filterForm.search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                }
            ),
        500
    )
)
</script>
